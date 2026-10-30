<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReceiptPrintJob;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OwnerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCafeOperator($request);
        $query = Order::with(['cafe', 'roastery', 'items', 'payments' => fn ($q) => $q->latest('id')])->latest();
        if ($request->user()->role !== 'developer') {
            $query->where(function ($query) use ($request): void {
                $query->whereHas('cafe', fn ($q) => $q->where('owner_id', $request->user()->id))
                    ->orWhereHas('roastery', fn ($q) => $q->where('owner_id', $request->user()->id));
            });
        }
        $orders = $query->paginate(25);

        return view('owner.orders.index', compact('orders'));
    }

    public function show(Request $request, string $code): View
    {
        $order = $this->scopedOrder($request, $code);
        $order->load(['cafe', 'roastery', 'items', 'payments' => fn ($q) => $q->latest('id'), 'statusHistories', 'printJobs']);

        return view('owner.orders.show', compact('order'));
    }

    public function confirmPayment(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $service->confirmPayment($order, $request->user());

        return redirect()->route('owner.orders.print', $code)->with('status', 'Pembayaran dikonfirmasi. Cetak nota fisik; pesanan baru dapat disiapkan setelah hasil cetak berhasil dicatat.');
    }

    public function confirmCashPayment(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $service->confirmCashPayment($order, $request->user());

        return redirect()->route('owner.orders.print', $code)->with('status', 'Pembayaran tunai dicatat. Cetak nota untuk pesanan.');
    }

    public function rejectPayment(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $service->rejectPayment($order, $request->user(), $validated['reason']);

        return redirect()->route('owner.orders.show', $code)->with('status', 'Bukti ditolak. Pembeli dapat mengirim bukti pembayaran yang benar.');
    }

    public function pickup(Request $request, OrderService $service): RedirectResponse
    {
        $this->ensureCafeOperator($request);
        $validated = $request->validate(['code' => ['required', 'string', 'alpha_num', 'min:8', 'max:20']]);

        try {
            $order = $this->scopedOrder($request, $validated['code']);
        } catch (ModelNotFoundException $exception) {
            return back()->withErrors(['code' => 'Kode pengambilan tidak dikenal.']);
        }

        if ($order->status !== Order::STATUS_READY) {
            return back()->withErrors(['code' => 'Pesanan belum Siap Diambil atau kode sudah pernah digunakan.']);
        }

        $service->transition($order, $request->user(), Order::STATUS_COMPLETED);

        return redirect()->route('owner.orders.index')->with('status', 'Pesanan diserahkan dan dicatat selesai.');
    }

    public function status(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $validated = $request->validate(['status' => ['required', 'in:preparing,ready,completed']]);
        $service->transition($order, $request->user(), $validated['status']);

        return redirect()->route('owner.orders.show', $code)->with('status', 'Status pesanan diperbarui.');
    }

    public function cancel(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $service->cancel($order, $request->user(), $validated['reason'] ?: 'Dibatalkan oleh kedai.');

        return redirect()->route('owner.orders.show', $code)->with('status', 'Pesanan dibatalkan dan reservasi stok dilepas.');
    }

    public function proof(Request $request, string $code, int $paymentId)
    {
        $order = $this->scopedOrder($request, $code);
        $payment = $order->payments()->findOrFail($paymentId);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }

    public function printReceipt(Request $request, string $code): View
    {
        $order = $this->scopedOrder($request, $code)->load(['cafe', 'roastery', 'items']);
        abort_unless($order->paid_at, 404);
        $job = $order->printJobs()->latest('id')->first();
        abort_unless($job, 404);

        return view('orders.receipt', compact('order', 'job'));
    }

    public function reprint(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $job = $service->createReprint($order, $request->user());

        return redirect()->route('owner.orders.print', $code)->with('print_job_id', $job->id);
    }

    public function updatePrintJob(Request $request, ReceiptPrintJob $receiptPrintJob, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $receiptPrintJob->order->public_code);
        abort_unless($receiptPrintJob->order_id === $order->id, 404);
        $validated = $request->validate([
            'state' => ['required', 'in:printed,failed,pending'],
            'last_error' => ['nullable', 'string', 'max:500'],
        ]);
        $service->recordPrintResult($order, $receiptPrintJob, $request->user(), $validated['state'], $validated['last_error'] ?? null);

        return redirect()->route('owner.orders.print', $order->public_code)->with('status', $validated['state'] === 'printed' ? 'Cetakan ditandai berhasil.' : 'Status cetak diperbarui; Anda dapat mencoba cetak ulang.');
    }

    private function scopedOrder(Request $request, string $code): Order
    {
        $this->ensureCafeOperator($request);
        $query = Order::query()->where('public_code', strtoupper($code));
        if ($request->user()->role !== 'developer') {
            $query->where(function ($query) use ($request): void {
                $query->whereHas('cafe', fn ($q) => $q->where('owner_id', $request->user()->id))
                    ->orWhereHas('roastery', fn ($q) => $q->where('owner_id', $request->user()->id));
            });
        }

        return $query->firstOrFail();
    }

    private function ensureCafeOperator(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['developer', 'admin', 'roastery'], true), 403);
    }
}
