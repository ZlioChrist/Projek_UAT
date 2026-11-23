<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceiptPrintJob;
use App\Models\Roastery;
use App\Models\User;
use App\Notifications\OrderPaymentVerifiedNotification;
use App\Notifications\OrderReadyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        ?string $senderBank = null,
        array $addons = [],
        string $fulfillmentMethod = 'pickup',
        ?string $deliveryAddress = null,
    ): Order {
        if (! in_array($fulfillmentMethod, ['pickup', 'delivery'], true)) {
            throw ValidationException::withMessages(['fulfillment_method' => 'Pilih cara menerima pesanan yang tersedia.']);
        }

        if ($fulfillmentMethod === 'delivery' && ! ($shop instanceof Cafe)) {
            throw ValidationException::withMessages(['fulfillment_method' => 'Pesanan antar hanya tersedia untuk kafe.']);
        }

        if ($fulfillmentMethod === 'delivery' && (
            $shop->delivery_base_fee === null
            || $shop->delivery_fee_per_km === null
        )) {
            throw ValidationException::withMessages(['fulfillment_method' => 'Pengantaran belum tersedia karena tarif ongkir kafe belum diatur.']);
        }

        if ($fulfillmentMethod === 'delivery' && blank($deliveryAddress)) {
            throw ValidationException::withMessages(['delivery_address' => 'Alamat pengantaran wajib diisi.']);
        }

        if ($fulfillmentMethod === 'delivery' && $paymentMethod === 'cash') {
            throw ValidationException::withMessages(['payment_method' => 'Pembayaran tunai di kedai hanya tersedia untuk pesanan ambil sendiri.']);
        }

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

        foreach ($addons as $parentId => $selected) {
            if (! ctype_digit((string) $parentId) || (int) $parentId < 1 || ! is_array($selected)) {
                throw ValidationException::withMessages(['cart' => 'Pilihan topping pada keranjang tidak valid.']);
            }
            foreach ($selected as $addonId => $quantity) {
                if (
                    ! ctype_digit((string) $addonId)
                    || (int) $addonId < 1
                    || ! is_numeric($quantity)
                    || (int) $quantity < 0
                    || (int) $quantity > 99
                ) {
                    throw ValidationException::withMessages(['cart' => 'Pilihan topping pada keranjang tidak valid.']);
                }
            }
        }

        $addons = collect($addons)
            ->mapWithKeys(fn ($selected, $parentId) => [
                (int) $parentId => collect($selected)
                    ->mapWithKeys(fn ($quantity, $addonId) => [(int) $addonId => (int) $quantity])
                    ->filter(fn ($quantity, $addonId) => $addonId > 0 && $quantity > 0)
                    ->all(),
            ])
            ->filter(fn ($selected, $parentId) => $parentId > 0 && $selected !== [])
            ->all();

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }

        $allQuantities = array_merge(
            array_values($quantities),
            collect($addons)->flatMap(fn ($selected) => array_values($selected))->all(),
        );
        if (max($allQuantities) > 99) {
            throw ValidationException::withMessages(['cart' => 'Maksimal 99 item per produk.']);
        }

        if (array_diff(array_keys($addons), array_keys($quantities)) !== []) {
            throw ValidationException::withMessages(['cart' => 'Topping harus terhubung ke menu utama di keranjang.']);
        }

        if (! in_array($paymentMethod, ['qris', 'transfer', 'cash'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak dikenal.']);
        }

        if ($paymentMethod !== 'transfer' && $senderBank !== null) {
            throw ValidationException::withMessages(['sender_bank' => 'Bank pengirim hanya dapat dipilih untuk transfer.']);
        }

        if ($senderBank !== null && ! array_key_exists($senderBank, config('payment.transfer_banks', []))) {
            throw ValidationException::withMessages(['sender_bank' => 'Bank pengirim tidak dikenal.']);
        }

        $this->expireDueOrders();

        return DB::transaction(function () use ($shop, $quantities, $addons, $customerName, $customerWhatsapp, $userId, $customerNote, $idempotencyKey, $paymentMethod, $senderBank, $fulfillmentMethod, $deliveryAddress) {
            if ($idempotencyKey && ($existing = Order::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load(['cafe', 'roastery', 'items']);
            }

            $addonIds = collect($addons)->flatMap(fn ($selected) => array_keys($selected))->unique()->values();
            $productIds = collect(array_keys($quantities))->merge($addonIds)->unique()->sort()->values();
            $menuItems = MenuItem::query()
                ->where($shop instanceof Cafe ? 'cafe_id' : 'roastery_id', $shop->id)
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Re-check after row locks so two identical concurrent POSTs reuse the first order.
            if ($idempotencyKey && ($existing = Order::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load(['cafe', 'roastery', 'items']);
            }

            if ($menuItems->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['cart' => 'Ada menu yang sudah tidak tersedia. Periksa keranjang Anda.']);
            }

            if (DB::table('product_addons')->whereIn('addon_product_id', array_keys($quantities))->exists()) {
                throw ValidationException::withMessages(['cart' => 'Topping hanya dapat dipesan bersama menu utamanya.']);
            }

            $addonLinks = DB::table('product_addons')
                ->whereIn('parent_product_id', array_keys($addons))
                ->whereIn('addon_product_id', $addonIds)
                ->get()
                ->mapWithKeys(fn ($link) => ["{$link->parent_product_id}:{$link->addon_product_id}" => true]);
            foreach ($addons as $parentId => $selected) {
                foreach ($selected as $addonId => $quantity) {
                    if (! $addonLinks->has("{$parentId}:{$addonId}")) {
                        throw ValidationException::withMessages([
                            'cart' => 'Ada topping yang tidak lagi tersedia untuk menu pilihan. Periksa kembali keranjang Anda.',
                        ]);
                    }
                }
            }

            $stockRequirements = $quantities;
            foreach ($addons as $selected) {
                foreach ($selected as $addonId => $quantity) {
                    $stockRequirements[$addonId] = ($stockRequirements[$addonId] ?? 0) + $quantity;
                }
            }

            $subtotalCents = 0;
            foreach ($stockRequirements as $id => $quantity) {
                $item = $menuItems->get($id);
                if (! $item || ! $item->is_active) {
                    throw ValidationException::withMessages(['cart' => 'Ada menu atau topping yang sudah tidak tersedia.']);
                }

                $available = max(0, (int) $item->stock - (int) $item->reserved_stock);
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Jumlah pesanan tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.',
                    ]);
                }

            }
            foreach ($quantities as $id => $quantity) {
                $subtotalCents += (int) round(((float) $menuItems->get($id)->price) * 100) * $quantity;
            }
            foreach ($addons as $selected) {
                foreach ($selected as $addonId => $quantity) {
                    $subtotalCents += (int) round(((float) $menuItems->get($addonId)->price) * 100) * $quantity;
                }
            }

            $total = number_format($subtotalCents / 100, 2, '.', '');
            $displayCode = $this->nextDisplayCode();
            $reservationMinutes = $fulfillmentMethod === 'delivery'
                ? (int) config('payment.delivery_fee_confirmation_minutes', 60)
                : (int) config(
                    $paymentMethod === 'cash' ? 'payment.cash_reservation_minutes' : 'payment.reservation_minutes',
                    $paymentMethod === 'cash' ? 30 : 15,
                );
            $order = Order::create([
                'cafe_id' => $shop instanceof Cafe ? $shop->id : null,
                'roastery_id' => $shop instanceof Roastery ? $shop->id : null,
                'user_id' => $userId,
                'public_code' => $this->newPublicCode(),
                'display_code' => $displayCode,
                'idempotency_key' => $idempotencyKey,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'sender_bank' => $senderBank,
                'fulfillment_method' => $fulfillmentMethod,
                'delivery_address' => $fulfillmentMethod === 'delivery' ? trim((string) $deliveryAddress) : null,
                'delivery_fee' => 0,
                'delivery_fee_confirmed_at' => $fulfillmentMethod === 'pickup' ? now() : null,
                'payment_expires_at' => now()->addMinutes($reservationMinutes),
                'customer_name' => trim($customerName),
                'customer_whatsapp' => $customerWhatsapp,
                'customer_note' => $customerNote,
                'subtotal' => $total,
                'total' => $total,
            ]);

            foreach ($quantities as $id => $quantity) {
                $item = $menuItems->get($id);
                $lineCents = (int) round(((float) $item->price) * 100) * $quantity;
                $parentOrderItem = $order->items()->create([
                    'product_id' => $item->id,
                    'name_snapshot' => $item->name,
                    'unit_price' => $item->price,
                    'quantity' => $quantity,
                    'line_total' => number_format($lineCents / 100, 2, '.', ''),
                ]);

                foreach ($addons[$id] ?? [] as $addonId => $addonQuantity) {
                    $addon = $menuItems->get($addonId);
                    $addonLineCents = (int) round(((float) $addon->price) * 100) * $addonQuantity;
                    $order->items()->create([
                        'product_id' => $addon->id,
                        'parent_order_item_id' => $parentOrderItem->id,
                        'name_snapshot' => $addon->addon_name,
                        'unit_price' => $addon->price,
                        'quantity' => $addonQuantity,
                        'line_total' => number_format($addonLineCents / 100, 2, '.', ''),
                    ]);
                }
            }

            foreach ($stockRequirements as $id => $quantity) {
                $item = $menuItems->get($id);
                $item->reserved_stock = (int) $item->reserved_stock + $quantity;
                $item->save();
            }

            $this->recordStatus($order, null, Order::STATUS_AWAITING_PAYMENT, $userId, 'Pesanan dibuat; stok direservasi sampai dibayar atau batas reservasi berakhir.');

            return $order->load(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function submitPayment(
        Order $order,
        string $proofPath,
        ?string $paymentMethod = null,
    ): OrderPayment {
        if ($proofPath === '' || ! Storage::disk('local')->exists($proofPath)) {
            throw ValidationException::withMessages(['proof' => 'Bukti pembayaran wajib diunggah sebelum pembayaran dikirim.']);
        }

        $payment = DB::transaction(function () use ($order, $proofPath, $paymentMethod) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_AWAITING_PAYMENT) {
                throw ValidationException::withMessages(['payment' => 'Pesanan tidak sedang menunggu pembayaran.']);
            }
            if (! in_array($locked->payment_method, ['qris', 'transfer'], true)) {
                throw ValidationException::withMessages(['payment' => 'Metode pembayaran ini tidak menerima unggahan bukti.']);
            }
            if ($paymentMethod !== null && $paymentMethod !== $locked->payment_method) {
                throw ValidationException::withMessages(['payment' => 'Metode pembayaran tidak sesuai dengan pesanan.']);
            }
            if ($locked->fulfillment_method === 'delivery' && ! $locked->delivery_fee_confirmed_at) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran belum dapat dikirim sampai kafe mengonfirmasi ongkir.']);
            }

            if ($locked->payment_expires_at && $locked->payment_expires_at->lte(now())) {
                $this->expireLockedOrder($locked);

                return null;
            }

            $payment = $locked->payments()->create([
                'reference' => null,
                'amount' => $locked->total,
                'payment_method' => $paymentMethod ?? $locked->payment_method,
                'proof_path' => $proofPath,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $from = $locked->status;
            $locked->status = Order::STATUS_PENDING_VERIFICATION;
            $locked->payment_status = 'submitted';
            $locked->payment_expires_at = null;
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, null, 'Bukti pembayaran dikirim; menunggu verifikasi kedai.');

            return $payment;
        }, 3);

        if (! $payment) {
            throw ValidationException::withMessages(['payment' => 'Batas waktu pembayaran telah berakhir. Pesanan dibatalkan dan stok dikembalikan. Silakan buat pesanan baru.']);
        }

        return $payment;
    }

    public function setDeliveryFee(Order $order, User $actor, string $distanceKm): Order
    {
        if (
            preg_match('/^\d+(?:\.\d{1,2})?$/D', $distanceKm) !== 1
            || (float) $distanceKm <= 0
            || (float) $distanceKm > 999999.99
        ) {
            throw ValidationException::withMessages(['distance_km' => 'Masukkan jarak rute yang valid dalam kilometer.']);
        }

        $distanceKm = number_format((float) $distanceKm, 2, '.', '');
        $roundedDistanceKm = (int) ceil((float) $distanceKm);

        $updated = DB::transaction(function () use ($order, $actor, $distanceKm, $roundedDistanceKm): ?Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (
                $locked->fulfillment_method !== 'delivery'
                || $locked->status !== Order::STATUS_AWAITING_PAYMENT
                || $locked->payment_status !== 'unpaid'
                || $locked->payments()->exists()
            ) {
                throw ValidationException::withMessages(['distance_km' => 'Jarak dan ongkir hanya dapat dihitung sebelum pembayaran dikirim.']);
            }

            if ($locked->payment_expires_at && $locked->payment_expires_at->lte(now())) {
                $this->expireLockedOrder($locked);

                return null;
            }

            $cafe = $locked->cafe;
            if (! $cafe || $cafe->delivery_base_fee === null || $cafe->delivery_fee_per_km === null) {
                throw ValidationException::withMessages(['distance_km' => 'Atur biaya dasar dan tarif per km pada pengaturan kafe terlebih dahulu.']);
            }

            $deliveryFee = (int) $cafe->delivery_base_fee
                + ($roundedDistanceKm * (int) $cafe->delivery_fee_per_km);
            if ($deliveryFee > 9999999999) {
                throw ValidationException::withMessages(['distance_km' => 'Hasil perhitungan ongkir melebihi batas nominal.']);
            }

            $feeCents = $deliveryFee * 100;
            $fee = number_format($feeCents / 100, 2, '.', '');
            $totalCents = (int) round(((float) $locked->subtotal) * 100) + $feeCents;
            if ($totalCents > 999999999999) {
                throw ValidationException::withMessages(['distance_km' => 'Total pesanan melebihi batas nominal yang diizinkan.']);
            }

            $locked->delivery_fee = $fee;
            $locked->delivery_distance_km = $distanceKm;
            $locked->total = number_format($totalCents / 100, 2, '.', '');
            $locked->delivery_fee_confirmed_at = now();
            $locked->payment_expires_at = now()->addMinutes((int) config('payment.reservation_minutes', 15));
            $locked->save();
            $this->recordStatus(
                $locked,
                $locked->status,
                $locked->status,
                $actor->id,
                "Kafe mengukur rute {$distanceKm} km (dibulatkan menjadi {$roundedDistanceKm} km); ongkir Rp".number_format($deliveryFee, 0, ',', '.').'; total pembayaran diperbarui.',
            );

            return $locked->fresh(['cafe', 'roastery', 'items']);
        }, 3);

        if (! $updated) {
            throw ValidationException::withMessages(['distance_km' => 'Batas waktu pesanan berakhir. Minta pelanggan membuat pesanan baru.']);
        }

        return $updated;
    }

    public function completeUnconfirmedOrders(): int
    {
        $completedCount = 0;
        $confirmationDeadline = now()->subHours(24);

        Order::query()
            ->whereIn('status', [
                Order::STATUS_READY,
                Order::STATUS_DELIVERING,
                Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
            ])
            ->whereNotNull('receipt_confirmation_deadline_started_at')
            ->where('receipt_confirmation_deadline_started_at', '<=', $confirmationDeadline)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$completedCount, $confirmationDeadline): void {
                foreach ($orders as $order) {
                    $completed = DB::transaction(function () use ($order, $confirmationDeadline): bool {
                        $locked = Order::query()->lockForUpdate()->find($order->id);
                        if (
                            ! $locked
                            || ! in_array($locked->status, [
                                Order::STATUS_READY,
                                Order::STATUS_DELIVERING,
                                Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
                            ], true)
                            || ! $locked->receipt_confirmation_deadline_started_at
                            || $locked->receipt_confirmation_deadline_started_at->gt($confirmationDeadline)
                        ) {
                            return false;
                        }

                        $from = $locked->status;
                        $locked->status = Order::STATUS_COMPLETED;
                        $locked->completed_at = now();
                        $locked->save();
                        $this->recordStatus(
                            $locked,
                            $from,
                            $locked->status,
                            null,
                            'Pesanan otomatis diselesaikan karena pelanggan tidak melaporkan kendala dalam 24 jam sejak siap diambil atau mulai diantar.',
                        );

                        return true;
                    }, 3);

                    if ($completed) {
                        $completedCount++;
                    }
                }
            });

        return $completedCount;
    }

    public function confirmReceived(Order $order, ?User $actor): Order
    {
        $this->completeUnconfirmedOrders();

        return DB::transaction(function () use ($order, $actor): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status === Order::STATUS_COMPLETED) {
                return $locked->fresh(['cafe', 'roastery', 'items']);
            }
            if ($locked->status !== Order::STATUS_AWAITING_RECEIPT_CONFIRMATION) {
                throw ValidationException::withMessages(['order' => 'Pesanan belum menunggu konfirmasi penerimaan atau sudah selesai.']);
            }

            if ($actor && $locked->user_id && (int) $locked->user_id !== (int) $actor->id) {
                abort(404);
            }

            $from = $locked->status;
            $locked->status = Order::STATUS_COMPLETED;
            $locked->completed_at = now();
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, $actor?->id, 'Pelanggan mengonfirmasi pesanan sudah diterima.');

            return $locked->fresh(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function markPickedUp(Order $order, User $actor): Order
    {
        $this->completeUnconfirmedOrders();

        return DB::transaction(function () use ($order, $actor): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== Order::STATUS_READY || $locked->fulfillment_method !== 'pickup') {
                throw ValidationException::withMessages(['order' => 'Pesanan belum siap diambil atau bukan pesanan ambil sendiri.']);
            }

            $from = $locked->status;
            $locked->status = Order::STATUS_AWAITING_RECEIPT_CONFIRMATION;
            $locked->save();
            $this->recordStatus($locked, $from, $locked->status, $actor->id, 'Kode dicocokkan; kafe mencatat pesanan telah diserahkan kepada pelanggan.');

            return $locked->fresh(['cafe', 'roastery', 'items']);
        }, 3);
    }

    public function expireDueOrders(): int
    {
        $expiredCount = 0;
        $now = now();

        Order::query()
            ->where('status', Order::STATUS_AWAITING_PAYMENT)
            ->whereIn('payment_method', ['qris', 'transfer', 'cash'])
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$expiredCount): void {
                foreach ($orders as $order) {
                    $expired = DB::transaction(function () use ($order): bool {
                        $locked = Order::query()->lockForUpdate()->find($order->id);
                        if (
                            ! $locked
                            || $locked->status !== Order::STATUS_AWAITING_PAYMENT
                            || ! $locked->payment_expires_at
                            || $locked->payment_expires_at->gt(now())
                        ) {
                            return false;
                        }

                        $this->expireLockedOrder($locked);

                        return true;
                    }, 3);

                    if ($expired) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function confirmCashPayment(Order $order, User $actor): Order
    {
        $confirmedOrder = DB::transaction(function () use ($order, $actor): ?Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (
                $locked->status !== Order::STATUS_AWAITING_PAYMENT
                || $locked->payment_method !== 'cash'
                || $locked->fulfillment_method === 'delivery'
            ) {
                throw ValidationException::withMessages(['payment' => 'Pesanan ini tidak dapat dikonfirmasi sebagai pembayaran tunai.']);
            }

            if ($locked->payment_expires_at && $locked->payment_expires_at->lte(now())) {
                $this->expireLockedOrder($locked);

                return null;
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

            return $this->completePayment($locked, $payment, $actor);
        }, 3);

        if (! $confirmedOrder) {
            throw ValidationException::withMessages(['payment' => 'Batas waktu reservasi tunai telah berakhir. Pesanan dibatalkan dan stok dikembalikan.']);
        }

        return $confirmedOrder;
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

            return $this->completePayment($locked, $payment, $actor);
        }, 3);
    }

    private function completePayment(Order $order, OrderPayment $payment, User $actor): Order
    {
        if (
            in_array($order->payment_method, ['qris', 'transfer'], true)
            && (! $payment->proof_path || ! Storage::disk('local')->exists($payment->proof_path))
        ) {
            throw ValidationException::withMessages(['payment' => 'Bukti pembayaran tidak tersedia. Pembayaran tidak dapat dikonfirmasi.']);
        }

        if ((int) round(((float) $payment->amount) * 100) !== (int) round(((float) $order->total) * 100)) {
            throw ValidationException::withMessages(['payment' => 'Nominal bukti tidak sama dengan total pesanan.']);
        }

        $orderItems = $order->items()->orderBy('product_id')->get();
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

        $from = $order->status;
        $order->status = Order::STATUS_PAID;
        $order->payment_status = 'paid';
        $order->paid_at = now();
        $order->payment_expires_at = null;
        $order->save();

        $payment->status = 'confirmed';
        $payment->verified_by = $actor->id;
        $payment->verified_at = now();
        $payment->save();
        $this->recordStatus($order, $from, $order->status, $actor->id, 'Pembayaran diverifikasi oleh kedai.');

        if ($order->user_id) {
            User::query()->findOrFail($order->user_id)->notify(new OrderPaymentVerifiedNotification($order));
        }

        ReceiptPrintJob::firstOrCreate(
            ['dedupe_key' => 'order-'.$order->id.'-automatic'],
            [
                'order_id' => $order->id,
                'kind' => 'automatic',
                'state' => 'pending',
                'payload' => $this->receiptPayload($order->fresh(['cafe', 'roastery', 'items'])),
            ],
        );

        return $order->fresh(['cafe', 'roastery', 'items', 'payments']);
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
            if ($locked->payment_method === 'qris') {
                $this->releaseReservedStock($locked);
                $locked->status = Order::STATUS_CANCELLED;
                $locked->payment_status = 'cancelled';
                $locked->cancelled_at = now();
                $locked->payment_expires_at = null;
                $locked->save();
                $this->recordStatus(
                    $locked,
                    $from,
                    $locked->status,
                    $actor->id,
                    'Bukti QRIS ditolak dan pesanan dibatalkan: '.$reason,
                );
            } else {
                $locked->status = Order::STATUS_AWAITING_PAYMENT;
                $locked->payment_status = 'rejected';
                $locked->payment_expires_at = now()->addMinutes((int) config('payment.reservation_minutes', 15));
                $locked->save();
                $this->recordStatus($locked, $from, $locked->status, $actor->id, 'Bukti pembayaran ditolak: '.$reason);
            }

            return $locked->fresh(['cafe', 'roastery', 'items', 'payments']);
        }, 3);
    }

    public function transition(Order $order, User $actor, string $target): Order
    {
        $this->completeUnconfirmedOrders();

        return DB::transaction(function () use ($order, $actor, $target) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $processingTarget = $locked->fulfillment_method === 'delivery'
                ? Order::STATUS_DELIVERING
                : Order::STATUS_READY;
            $allowed = [
                Order::STATUS_PAID => [$processingTarget],
                Order::STATUS_PREPARING => [$processingTarget],
                Order::STATUS_READY => $locked->fulfillment_method === 'delivery'
                    ? [Order::STATUS_DELIVERING]
                    : [],
                Order::STATUS_DELIVERING => [Order::STATUS_AWAITING_RECEIPT_CONFIRMATION],
            ];

            if (
                in_array($target, [Order::STATUS_READY, Order::STATUS_DELIVERING], true)
                && in_array($locked->status, [Order::STATUS_PAID, Order::STATUS_PREPARING], true)
            ) {
                if ($locked->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['status' => 'Pembayaran belum lunas. Pesanan belum dapat diproses.']);
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
                $message = $locked->payment_status !== 'paid'
                    && in_array($target, [Order::STATUS_READY, Order::STATUS_DELIVERING], true)
                    ? 'Pembayaran belum masuk. Pesanan belum dapat diproses.'
                    : 'Perubahan status tidak valid atau sudah dilakukan.';
                throw ValidationException::withMessages(['status' => $message]);
            }

            $from = $locked->status;
            $locked->status = $target;
            if ($target === Order::STATUS_READY) {
                $locked->ready_at = now();
                if ($locked->fulfillment_method === 'pickup') {
                    $locked->receipt_confirmation_deadline_started_at = now();
                }
            } elseif ($target === Order::STATUS_DELIVERING) {
                $locked->delivery_started_at = now();
                $locked->receipt_confirmation_deadline_started_at = now();
            }
            $locked->save();
            $this->recordStatus($locked, $from, $target, $actor->id, 'Status diperbarui oleh kedai.');

            if (in_array($target, [Order::STATUS_READY, Order::STATUS_DELIVERING, Order::STATUS_AWAITING_RECEIPT_CONFIRMATION], true) && $locked->user_id) {
                User::query()->findOrFail($locked->user_id)->notify(new OrderReadyNotification($locked));
            }

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

            return $this->cancelLockedOrder($locked, $actor, $reason);
        }, 3);
    }

    public function cancelByCustomer(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->canBeCancelledByCustomer()) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan hanya dapat dibatalkan sebelum pembayaran atau bukti pembayaran dikirim.',
                ]);
            }

            return $this->cancelLockedOrder($locked, $actor, 'Pesanan dibatalkan oleh customer sebelum pembayaran.');
        }, 3);
    }

    private function cancelLockedOrder(Order $order, ?User $actor, string $reason): Order
    {
        $this->releaseReservedStock($order);

        $submittedPayment = $order->payments()
            ->where('status', 'submitted')
            ->latest('id')
            ->lockForUpdate()
            ->first();
        if ($submittedPayment) {
            $submittedPayment->status = 'rejected';
            $submittedPayment->verified_by = $actor?->id;
            $submittedPayment->verified_at = now();
            $submittedPayment->rejection_reason = $reason;
            $submittedPayment->save();
        }

        $from = $order->status;
        $order->status = Order::STATUS_CANCELLED;
        $order->payment_status = 'cancelled';
        $order->cancelled_at = now();
        $order->payment_expires_at = null;
        $order->save();
        $this->recordStatus($order, $from, $order->status, $actor?->id, $reason);

        return $order->fresh(['cafe', 'roastery', 'items']);
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
            'order_code' => $order->display_code,
            'customer_name' => $order->customer_name,
            'fulfillment_method' => $order->fulfillment_method,
            'delivery_address' => $order->delivery_address,
            'delivery_distance_km' => $order->delivery_distance_km,
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->parent_order_item_id ? '+ '.$item->name_snapshot : $item->name_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'total' => $order->total,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
            'pickup_code' => $order->display_code,
        ];
    }

    private function releaseReservedStock(Order $order): void
    {
        $orderItems = $order->items()->orderBy('product_id')->get();
        $menuIds = $orderItems->pluck('product_id')->filter()->unique()->sort()->values();
        $menuItems = MenuItem::query()->whereIn('id', $menuIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($orderItems as $orderItem) {
            if ($menuItem = $menuItems->get($orderItem->product_id)) {
                $menuItem->reserved_stock = max(0, (int) $menuItem->reserved_stock - (int) $orderItem->quantity);
                $menuItem->save();
            }
        }
    }

    private function expireLockedOrder(Order $order): void
    {
        $this->releaseReservedStock($order);

        $from = $order->status;
        $order->status = Order::STATUS_CANCELLED;
        $order->payment_status = 'expired';
        $order->cancelled_at = now();
        $order->payment_expires_at = null;
        $order->save();
        $this->recordStatus(
            $order,
            $from,
            $order->status,
            null,
            'Batas waktu pembayaran berakhir; pesanan dibatalkan dan reservasi stok dilepas.',
        );
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

    private function nextDisplayCode(): string
    {
        $sequence = DB::table('order_display_code_sequences')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        if (! $sequence || (int) $sequence->next_value > 9999) {
            throw ValidationException::withMessages([
                'checkout' => 'Kode pesanan WadahNgopi sudah mencapai kapasitas 9.999 nomor. Hubungi admin untuk bantuan.',
            ]);
        }

        $number = (int) $sequence->next_value;
        DB::table('order_display_code_sequences')
            ->where('id', 1)
            ->update(['next_value' => $number + 1]);

        return 'WN-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
