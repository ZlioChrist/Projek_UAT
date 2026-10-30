<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceiptPrintJob;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create an unpaid pickup order and reserve its stock atomically.
     * Stock is not consumed until a cafe confirms payment.
     */
    public function placeOrder(
        Cafe|Roastery $shop,
        array $quantities,
        string $customerName,
        ?string $customerWhatsapp,
        ?int $userId,
        ?string $customerNote = null,
        ?string $idempotencyKey = null,
        string $paymentMethod = 'qris',
    ): Order {
        if ($customerWhatsapp !== null) {
            $customerWhatsapp = preg_replace('/\D+/', '', $customerWhatsapp);
            if (str_starts_with($customerWhatsapp, '0')) {
                $customerWhatsapp = '62'.substr($customerWhatsapp, 1);
            } elseif ($customerWhatsapp !== '' && ! str_starts_with($customerWhatsapp, '62')) {
                $customerWhatsapp = '62'.$customerWhatsapp;
            }
        }

        $quantities = collect($quantities)
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity, $id) => $id > 0 && $quantity > 0)
            ->all();

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }

        if (max($quantities) > 99) {
            throw ValidationException::withMessages(['cart' => 'Maksimal 99 item per produk.']);
        }

        if (! in_array($paymentMethod, ['qris', 'transfer', 'cash'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak dikenal.']);
        }

        return DB::transaction(function () use ($shop, $quantities, $customerName, $customerWhatsapp, $userId, $customerNote, $idempotencyKey, $paymentMethod) {
            if ($idempotencyKey && ($existing = Order::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load(['cafe', 'roastery', 'items']);
            }

            $menuItems = MenuItem::query()
                ->where($shop instanceof Cafe ? 'cafe_id' : 'roastery_id', $shop->id)
                ->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Re-check after row locks so two identical concurrent POSTs reuse the first order.
            if ($idempotencyKey && ($existing = Order::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load(['cafe', 'roastery', 'items']);
            }

            if ($menuItems->count() !== count($quantities)) {
                throw ValidationException::withMessages(['cart' => 'Ada menu yang sudah tidak tersedia. Periksa keranjang Anda.']);
            }

            $subtotalCents = 0;
            foreach ($quantities as $id => $quantity) {
                $item = $menuItems->get($id);
                if (! $item || ! $item->is_active) {
                    throw ValidationException::withMessages(['cart' => 'Ada menu yang sudah tidak tersedia.']);
                }

                $available = max(0, (int) $item->stock - (int) $item->reserved_stock);
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Jumlah pesanan tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.',
                    ]);
                }

                $subtotalCents += (int) round(((float) $item->price) * 100) * $quantity;
            }

            $total = number_format($subtotalCents / 100, 2, '.', '');
            $order = Order::create([
                'cafe_id' => $shop instanceof Cafe ? $shop->id : null,
                'roastery_id' => $shop instanceof Roastery ? $shop->id : null,
                'user_id' => $userId,
                'public_code' => $this->newPublicCode(),
                'idempotency_key' => $idempotencyKey,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'customer_name' => trim($customerName),
                'customer_whatsapp' => $customerWhatsapp,
                'customer_note' => $customerNote,
                'subtotal' => $total,
                'total' => $total,
            ]);

            foreach ($quantities as $id => $quantity) {
                $item = $menuItems->get($id);
                $item->reserved_stock = (int) $item->reserved_stock + $quantity;
                $item->save();

                $lineCents = (int) round(((float) $item->price) * 100) * $quantity;
                $order->items()->create([
                    'product_id' => $item->id,
                    'name_snapshot' => $item->name,
                    'unit_price' => $item->price,
                    'quantity' => $quantity,
                    'line_total' => number_format($lineCents / 100, 2, '.', ''),
                ]);
            }

            $this->recordStatus($order, null, Order::STATUS_AWAITING_PAYMENT, $userId, 'Pesanan dibuat; stok direservasi sampai pembayaran diverifikasi.');

            return $order->load(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function submitPayment(
        Order $order,
        string $reference,
        string $amount,
        string $proofPath,
        ?string $paymentMethod = null,
    ): OrderPayment {
        return DB::transaction(function () use ($order, $reference, $amount, $proofPath, $paymentMethod) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_AWAITING_PAYMENT) {
                throw ValidationException::withMessages(['payment' => 'Pesanan tidak sedang menunggu pembayaran.']);
            }
            if ($paymentMethod !== null && $paymentMethod !== $locked->payment_method) {
                throw ValidationException::withMessages(['payment' => 'Metode pembayaran tidak sesuai dengan pesanan.']);
            }

            if ((int) round(((float) $amount) * 100) !== (int) round(((float) $locked->total) * 100)) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus sama persis dengan total pesanan.']);
            }

            if (OrderPayment::where('reference', $reference)->exists()) {
                throw ValidationException::withMessages(['reference' => 'Nomor referensi pembayaran ini sudah pernah digunakan.']);
            }

            $payment = $locked->payments()->create([
                'reference' => trim($reference),
                'amount' => $amount,
                'payment_method' => $paymentMethod ?? $locked->payment_method,
                'proof_path' => $proofPath,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $from = $locked->status;
            $locked->status = Order::STATUS_PENDING_VERIFICATION;
            $locked->payment_status = 'submitted';
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, null, 'Bukti pembayaran dikirim; menunggu verifikasi kedai.');

            return $payment;
        }, 3);
    }

    public function confirmCashPayment(Order $order, User $actor): Order
    {
        DB::transaction(function () use ($order, $actor): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_AWAITING_PAYMENT || $locked->payment_method !== 'cash') {
                throw ValidationException::withMessages(['payment' => 'Pesanan ini tidak dapat dikonfirmasi sebagai pembayaran tunai.']);
            }

            $payment = $locked->payments()->create([
                'reference' => null,
                'amount' => $locked->total,
                'payment_method' => 'cash',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $locked->status = Order::STATUS_PENDING_VERIFICATION;
            $locked->payment_status = 'submitted';
            $locked->save();
            $this->recordStatus($locked, Order::STATUS_AWAITING_PAYMENT, $locked->status, $actor->id, 'Pembayaran tunai dicatat staf dan menunggu konfirmasi.');
        }, 3);

        return $this->confirmPayment($order, $actor);
    }

    public function confirmPayment(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_PENDING_VERIFICATION) {
                throw ValidationException::withMessages(['payment' => 'Pesanan belum memiliki pembayaran yang menunggu verifikasi.']);
            }

            $payment = $locked->payments()->where('status', 'submitted')->latest('id')->lockForUpdate()->first();
            if (! $payment) {
                throw ValidationException::withMessages(['payment' => 'Bukti pembayaran tidak ditemukan atau sudah diproses.']);
            }

            if ((int) round(((float) $payment->amount) * 100) !== (int) round(((float) $locked->total) * 100)) {
                throw ValidationException::withMessages(['payment' => 'Nominal bukti tidak sama dengan total pesanan.']);
            }

            $orderItems = $locked->items()->orderBy('product_id')->get();
            $menuIds = $orderItems->pluck('product_id')->filter()->unique()->sort()->values();
            $menuItems = MenuItem::query()->whereIn('id', $menuIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($orderItems as $orderItem) {
                $menuItem = $menuItems->get($orderItem->product_id);
                if (! $menuItem || (int) $menuItem->stock < (int) $orderItem->quantity || (int) $menuItem->reserved_stock < (int) $orderItem->quantity) {
                    throw ValidationException::withMessages(['payment' => "Stok untuk {$orderItem->name_snapshot} berubah dan tidak cukup. Batalkan/refund transaksi melalui kedai."]);
                }
            }

            foreach ($orderItems as $orderItem) {
                $menuItem = $menuItems->get($orderItem->product_id);
                $menuItem->stock = (int) $menuItem->stock - (int) $orderItem->quantity;
                $menuItem->reserved_stock = (int) $menuItem->reserved_stock - (int) $orderItem->quantity;
                $menuItem->save();
            }

            $from = $locked->status;
            $locked->status = Order::STATUS_PAID;
            $locked->payment_status = 'paid';
            $locked->paid_at = now();
            $locked->save();

            $payment->status = 'confirmed';
            $payment->verified_by = $actor->id;
            $payment->verified_at = now();
            $payment->save();
            $this->recordStatus($locked, $from, $locked->status, $actor->id, 'Pembayaran diverifikasi manual oleh kedai.');

            ReceiptPrintJob::firstOrCreate(
                ['dedupe_key' => 'order-'.$locked->id.'-automatic'],
                [
                    'order_id' => $locked->id,
                    'kind' => 'automatic',
                    'state' => 'pending',
                    'payload' => $this->receiptPayload($locked->fresh(['cafe', 'items'])),
                ],
            );

            return $locked->fresh(['cafe', 'roastery', 'items', 'payments']);
        }, 3);
    }

    public function rejectPayment(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_PENDING_VERIFICATION) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini tidak dapat ditolak pada status sekarang.']);
            }

            $payment = $locked->payments()->where('status', 'submitted')->latest('id')->lockForUpdate()->first();
            if (! $payment) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran sudah diproses.']);
            }

            $payment->status = 'rejected';
            $payment->verified_by = $actor->id;
            $payment->verified_at = now();
            $payment->rejection_reason = $reason;
            $payment->save();

            $from = $locked->status;
            $locked->status = Order::STATUS_AWAITING_PAYMENT;
            $locked->payment_status = 'rejected';
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, $actor->id, 'Bukti pembayaran ditolak: '.$reason);

            return $locked->fresh(['cafe', 'roastery', 'items', 'payments']);
        }, 3);
    }

    public function transition(Order $order, User $actor, string $target): Order
    {
        $allowed = [
            Order::STATUS_PAID => [Order::STATUS_PREPARING],
            Order::STATUS_PREPARING => [Order::STATUS_READY],
            Order::STATUS_READY => [Order::STATUS_COMPLETED],
        ];

        return DB::transaction(function () use ($order, $actor, $target, $allowed) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($target === Order::STATUS_PREPARING && $locked->status === Order::STATUS_PAID) {
                if ($locked->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['status' => 'Pembayaran belum lunas. Pesanan belum dapat disiapkan.']);
                }

                $receiptPrinted = ReceiptPrintJob::query()
                    ->where('order_id', $locked->id)
                    ->where('state', 'printed')
                    ->exists();

                if (! $receiptPrinted) {
                    throw ValidationException::withMessages(['status' => 'Nota fisik belum tercatat berhasil dicetak. Pesanan belum boleh disiapkan.']);
                }
            }

            if (! in_array($target, $allowed[$locked->status] ?? [], true)) {
                $message = $locked->payment_status !== 'paid' && $target === Order::STATUS_PREPARING
                    ? 'Pembayaran belum masuk. Pesanan belum dapat disiapkan.'
                    : 'Perubahan status tidak valid atau sudah dilakukan.';
                throw ValidationException::withMessages(['status' => $message]);
            }

            $from = $locked->status;
            $locked->status = $target;
            if ($target === Order::STATUS_PREPARING) {
                $locked->preparing_at = now();
            } elseif ($target === Order::STATUS_READY) {
                $locked->ready_at = now();
            } elseif ($target === Order::STATUS_COMPLETED) {
                $locked->completed_at = now();
            }
            $locked->save();
            $this->recordStatus($locked, $from, $target, $actor->id, 'Status diperbarui oleh kedai.');

            return $locked->fresh(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function cancel(Order $order, ?User $actor, string $reason = 'Pesanan dibatalkan.'): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->canBeCancelled()) {
                throw ValidationException::withMessages(['order' => 'Pesanan yang sudah dibayar atau diproses tidak dapat dibatalkan lewat fitur ini.']);
            }

            $orderItems = $locked->items()->orderBy('product_id')->get();
            $menuIds = $orderItems->pluck('product_id')->filter()->unique()->sort()->values();
            $menuItems = MenuItem::query()->whereIn('id', $menuIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($orderItems as $orderItem) {
                if ($menuItem = $menuItems->get($orderItem->product_id)) {
                    $menuItem->reserved_stock = max(0, (int) $menuItem->reserved_stock - (int) $orderItem->quantity);
                    $menuItem->save();
                }
            }

            $from = $locked->status;
            $locked->status = Order::STATUS_CANCELLED;
            $locked->payment_status = 'cancelled';
            $locked->cancelled_at = now();
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, $actor?->id, $reason);

            return $locked->fresh(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function recordPrintResult(Order $order, ReceiptPrintJob $job, User $actor, string $state, ?string $error = null): ReceiptPrintJob
    {
        if (! in_array($state, ['printed', 'failed', 'pending'], true)) {
            throw ValidationException::withMessages(['state' => 'Status cetak tidak dikenal.']);
        }

        return DB::transaction(function () use ($order, $job, $actor, $state, $error) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $lockedJob = ReceiptPrintJob::query()->lockForUpdate()->findOrFail($job->id);

            if ($lockedJob->order_id !== $lockedOrder->id) {
                throw ValidationException::withMessages(['state' => 'Pekerjaan cetak tidak terkait dengan pesanan ini.']);
            }
            if ($lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages(['state' => 'Nota hanya dapat dicetak setelah pembayaran lunas.']);
            }
            if ($lockedJob->state === 'printed') {
                if ($state !== 'printed') {
                    throw ValidationException::withMessages(['state' => 'Catatan cetak berhasil tidak dapat diturunkan kembali. Buat pekerjaan cetak ulang bila perlu.']);
                }

                return $lockedJob;
            }

            $lockedJob->state = $state;
            $lockedJob->attempts = (int) $lockedJob->attempts + 1;
            $lockedJob->last_error = $state === 'failed' ? (trim((string) $error) ?: 'Printer gagal mencetak; periksa perangkat dan kertas.') : null;
            $lockedJob->printed_at = $state === 'printed' ? now() : null;
            $lockedJob->reported_by = $actor->id;
            $lockedJob->reported_at = now();
            $lockedJob->save();

            return $lockedJob->fresh();
        }, 3);
    }

    public function createReprint(Order $order, User $actor): ReceiptPrintJob
    {
        if (! $order->paid_at) {
            throw ValidationException::withMessages(['receipt' => 'Nota hanya tersedia setelah pembayaran lunas.']);
        }

        $order->loadMissing(['cafe', 'roastery', 'items']);

        return ReceiptPrintJob::create([
            'order_id' => $order->id,
            'kind' => 'reprint',
            'state' => 'pending',
            'dedupe_key' => 'order-'.$order->id.'-reprint-'.Str::uuid(),
            'payload' => $this->receiptPayload($order),
            'requested_by' => $actor->id,
        ]);
    }

    public function receiptPayload(Order $order): array
    {
        $order->loadMissing(['cafe', 'roastery', 'items']);

        return [
            'cafe_name' => $order->shop_name,
            'cafe_address' => $order->shop?->address,
            'order_code' => $order->public_code,
            'customer_name' => $order->customer_name,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->name_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'total' => $order->total,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
            'pickup_code' => $order->public_code,
        ];
    }

    private function recordStatus(Order $order, ?string $from, string $to, ?int $actorId, ?string $note): void
    {
        $order->statusHistories()->create([
            'changed_by' => $actorId,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function newPublicCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (Order::where('public_code', $code)->exists());

        return $code;
    }
}
