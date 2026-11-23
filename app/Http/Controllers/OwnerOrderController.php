<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Dashboard;
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
    public function dashboard(Request $request): RedirectResponse
    {
        $this->ensureCafeOperator($request);

        return redirect(Dashboard::getUrl());
    }

    public function index(Request $request, OrderService $service): View
    {
        $this->ensureCafeOperator($request);
        $service->expireDueOrders();
        $service->completeUnconfirmedOrders();
        $query = Order::with(['cafe', 'roastery', 'items', 'payments' => fn ($q) => $q->latest('id')])
            ->whereIn('status', [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PENDING_VERIFICATION]);
        if ($request->user()->role !== 'developer') {
            $query->where(function ($query) use ($request): void {
                $query->whereHas('cafe', fn ($q) => $q->where('owner_id', $request->user()->id))
                    ->orWhereHas('roastery', fn ($q) => $q->where('owner_id', $request->user()->id));
            });
        }
        $orders = $query->latest()->paginate(25);

        return view('owner.orders.index', compact('orders'));
    }

    public function show(Request $request, string $code, OrderService $service): View
    {
        $service->expireDueOrders();
        $service->completeUnconfirmedOrders();
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

        $message = $order->payment_method === 'qris'
            ? 'Bukti QRIS ditolak. Pesanan dibatalkan dan reservasi stok dilepas.'
            : 'Bukti ditolak. Pembeli dapat mengirim bukti pembayaran yang benar.';

        return redirect()->route('owner.orders.show', $code)->with('status', $message);
    }

    public function setDeliveryFee(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $validated = $request->validate([
            'distance_km' => ['required', 'numeric', 'gt:0', 'max:999999.99', 'regex:/^\\d+(?:\\.\\d{1,2})?$/'],
        ], [
            'distance_km.required' => 'Jarak rute wajib diisi.',
            'distance_km.numeric' => 'Jarak rute harus berupa angka dalam kilometer.',
            'distance_km.gt' => 'Jarak rute harus lebih dari 0 km.',
            'distance_km.max' => 'Jarak rute terlalu besar.',
            'distance_km.regex' => 'Jarak rute maksimal menggunakan dua angka desimal.',
        ]);
        $service->setDeliveryFee($order, $request->user(), (string) $validated['distance_km']);

        return redirect()->route('owner.orders.show', $code)->with('status', 'Jarak rute disimpan, ongkir dihitung sesuai tarif kafe, dan total pesanan diperbarui. Pelanggan dapat melanjutkan pembayaran.');
    }

    public function pickup(Request $request, OrderService $service): RedirectResponse
    {
        $this->ensureCafeOperator($request);
        $validated = $request->validate(['code' => ['required', 'string', 'regex:/^WN-\d{4}$/i']]);

        try {
            $order = $this->scopedOrderByDisplayCode($request, $validated['code']);
        } catch (ModelNotFoundException $exception) {
            return back()->withErrors(['code' => 'Kode pengambilan tidak dikenal.']);
        }

        if ($order->status !== Order::STATUS_READY || $order->fulfillment_method !== 'pickup') {
            return back()->withErrors(['code' => 'Pesanan belum Siap Diambil, bukan pesanan ambil sendiri, atau kode sudah pernah digunakan.']);
        }

        $service->markPickedUp($order, $request->user());

        return redirect()->route('owner.orders.index')->with('status', 'Serah-terima dicatat. Pelanggan diminta mengonfirmasi pesanan sudah diterima.');
    }

    public function status(Request $request, string $code, OrderService $service): RedirectResponse
    {
        $order = $this->scopedOrder($request, $code);
        $validated = $request->validate([
            'status' => ['required', 'in:ready,delivering,awaiting_receipt_confirmation'],
        ]);
        if (in_array($validated['status'], [Order::STATUS_READY, Order::STATUS_DELIVERING], true)) {
            $request->validate(['ready_confirmed' => ['required', 'accepted']]);
        }
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

    private function scopedOrderByDisplayCode(Request $request, string $code): Order
    {
        $this->ensureCafeOperator($request);
        $query = Order::query()->where('display_code', strtoupper($code));
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
