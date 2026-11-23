<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Roastery;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function cart(Request $request): View
    {
        $cart = $request->session()->get('order_cart', ['cafe_id' => null, 'items' => []]);
        $type = $cart['seller_type'] ?? (! empty($cart['cafe_id']) ? 'cafe' : null);
        $sellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? null;
        $cafe = match ($type) {
            'roastery' => Roastery::find($sellerId),
            'cafe' => Cafe::find($sellerId),
            default => null,
        };
        $items = collect();
        $addonsByParent = collect();
        $additionalItems = collect();

        if ($cafe && ! empty($cart['items'])) {
            $sellerColumn = $cafe instanceof Cafe ? 'cafe_id' : 'roastery_id';
            $shopItems = MenuItem::query()->where($sellerColumn, $cafe->id);
            $items = (clone $shopItems)
                ->whereIn('id', array_keys($cart['items']))
                ->whereDoesntHave('addonFor')
                ->get()
                ->map(function (MenuItem $item) use ($cart) {
                    $item->cart_quantity = (int) ($cart['items'][$item->id] ?? 0);

                    return $item;
                });
            $addonsByParent = $this->selectedCartAddons($cafe, $cart);
            $additionalItems = $shopItems
                ->whereNotIn('id', array_keys($cart['items']))
                ->whereDoesntHave('addonFor')
                ->where('is_active', true)
                ->whereRaw('stock > reserved_stock')
                ->orderBy('name')
                ->get();
        }

        return view('orders.cart', compact('cart', 'cafe', 'items', 'addonsByParent', 'additionalItems'));
    }

    public function addItem(Request $request, Cafe $cafe, MenuItem $menuItem): RedirectResponse|JsonResponse
    {
        return $this->addItemForShop($request, $cafe, $menuItem);
    }

    public function addRoasteryItem(Request $request, Roastery $roastery, MenuItem $menuItem): RedirectResponse|JsonResponse
    {
        return $this->addItemForShop($request, $roastery, $menuItem);
    }

    private function addItemForShop(Request $request, Cafe|Roastery $shop, MenuItem $menuItem): RedirectResponse|JsonResponse
    {
        abort_unless($shop->status === 'published', 404);
        $sellerColumn = $shop instanceof Cafe ? 'cafe_id' : 'roastery_id';
        abort_unless((int) $menuItem->{$sellerColumn} === (int) $shop->id && $menuItem->is_active, 404);
        abort_unless(! $menuItem->addonFor()->exists(), 404);

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'addon_quantities' => ['sometimes', 'array', 'max:50'],
            'addon_quantities.*' => ['required', 'integer', 'min:0', 'max:99'],
        ]);
        foreach (array_keys($validated['addon_quantities'] ?? []) as $addonId) {
            if (! ctype_digit((string) $addonId) || (int) $addonId < 1) {
                throw ValidationException::withMessages([
                    'addon_quantities' => 'Pilihan topping tidak valid. Muat ulang halaman lalu pilih kembali.',
                ]);
            }
        }
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);
        $requestedAddonQuantities = collect($validated['addon_quantities'] ?? [])
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity, $id) => $id > 0 && $quantity > 0)
            ->all();

        $cart = $request->session()->get('order_cart', ['seller_type' => null, 'seller_id' => null, 'items' => []]);
        $cartType = $cart['seller_type'] ?? (! empty($cart['cafe_id']) ? 'cafe' : null);
        $cartSellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? null;
        if ($cartType && ($cartType !== ($shop instanceof Cafe ? 'cafe' : 'roastery') || (int) $cartSellerId !== (int) $shop->id) && ! empty($cart['items'])) {
            $message = 'Satu pesanan hanya boleh berisi produk dari satu kedai. Selesaikan pesanan sebelumnya atau kosongkan keranjang untuk memesan dari kedai ini.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors([
                'cart' => $message,
            ]);
        }

        $available = max(0, (int) $menuItem->stock - (int) $menuItem->reserved_stock);
        $current = (int) ($cart['items'][$menuItem->id] ?? 0);
        if ($current + $quantityToAdd > $available) {
            $message = 'Menu ini sedang tidak tersedia untuk ditambahkan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['cart' => 'Menu ini sedang tidak tersedia untuk ditambahkan.']);
        }

        if ($requestedAddonQuantities !== []) {
            $validAddons = $menuItem->addons()
                ->where($sellerColumn, $shop->id)
                ->where('is_active', true)
                ->whereIn('products.id', array_keys($requestedAddonQuantities))
                ->get()
                ->keyBy('id');
            if ($validAddons->count() !== count($requestedAddonQuantities)) {
                throw ValidationException::withMessages([
                    'addon_quantities' => 'Ada topping yang tidak tersedia untuk menu ini. Muat ulang halaman lalu pilih kembali.',
                ]);
            }

            foreach ($requestedAddonQuantities as $addonId => $addonQuantity) {
                $addon = $validAddons->get($addonId);
                $existingQuantity = collect($cart['addons'] ?? [])
                    ->sum(fn ($selected) => (int) ($selected[$addonId] ?? 0));
                if ($addonQuantity + $existingQuantity > $addon->available_stock) {
                    throw ValidationException::withMessages([
                        'addon_quantities' => "Stok topping {$addon->addon_name} tidak mencukupi.",
                    ]);
                }
            }
        }

        $cart['seller_type'] = $shop instanceof Cafe ? 'cafe' : 'roastery';
        $cart['seller_id'] = $shop->id;
        $cart['cafe_id'] = $shop instanceof Cafe ? $shop->id : null;
        $cart['items'][$menuItem->id] = $current + $quantityToAdd;
        foreach ($requestedAddonQuantities as $addonId => $addonQuantity) {
            $cart['addons'][$menuItem->id][$addonId] =
                (int) ($cart['addons'][$menuItem->id][$addonId] ?? 0) + $addonQuantity;
        }
        $request->session()->put('order_cart', $cart);

        if ($request->expectsJson()) {
            $cartItems = MenuItem::query()
                ->where($sellerColumn, $shop->id)
                ->whereIn('id', array_keys($cart['items']))
                ->whereDoesntHave('addonFor')
                ->get(['id', 'name', 'price'])
                ->map(function (MenuItem $item) use ($cart): array {
                    $quantity = max(0, (int) ($cart['items'][$item->id] ?? 0));
                    $price = (float) $item->price;

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'line_total' => $price * $quantity,
                    ];
                })
                ->values();
            $addonsByParent = $this->selectedCartAddons($shop, $cart);
            $addonTotal = $addonsByParent->flatten(1)->sum(
                fn (MenuItem $addon) => (float) $addon->price * (int) $addon->cart_quantity,
            );
            $cartCount = collect($cart['items'])->sum(fn ($value) => max(0, (int) $value))
                + collect($cart['addons'] ?? [])->flatten()->sum(fn ($value) => max(0, (int) $value));

            return response()->json([
                'message' => 'Menu ditambahkan ke keranjang.',
                'added_item_id' => $menuItem->id,
                'shop_name' => $shop->name,
                'items' => $cartItems,
                'cart_count' => $cartCount,
                'total' => $cartItems->sum('line_total') + $addonTotal,
            ]);
        }

        return redirect()->route('cart.show')->with('status', 'Menu ditambahkan ke keranjang.');
    }

    public function updateItem(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart = $request->session()->get('order_cart', ['cafe_id' => null, 'items' => []]);
        abort_unless(isset($cart['items'][$menuItem->id]), 404);
        $expectedSellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? null;
        $sellerId = $menuItem->cafe_id ?? $menuItem->roastery_id;
        abort_unless((int) $expectedSellerId === (int) $sellerId, 404);
        abort_unless(! $menuItem->addonFor()->exists(), 404);

        $quantity = (int) $validated['quantity'];
        $available = max(0, (int) $menuItem->stock - (int) $menuItem->reserved_stock);
        if ($quantity > $available) {
            return back()->withErrors(['quantity' => 'Jumlah pesanan tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.']);
        }

        if ($quantity === 0) {
            unset($cart['items'][$menuItem->id]);
            unset($cart['addons'][$menuItem->id]);
        } else {
            $cart['items'][$menuItem->id] = $quantity;
        }
        if (empty($cart['items'])) {
            $cart = ['seller_type' => null, 'seller_id' => null, 'cafe_id' => null, 'items' => []];
        }
        $request->session()->put('order_cart', $cart);

        return back()->with('status', 'Keranjang diperbarui.');
    }

    public function updateAddon(Request $request, MenuItem $menuItem, MenuItem $addonMenuItem): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart = $request->session()->get('order_cart', ['seller_id' => null, 'items' => []]);
        abort_unless(isset($cart['items'][$menuItem->id]), 404);

        $expectedSellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? null;
        $sellerId = $menuItem->cafe_id ?? $menuItem->roastery_id;
        abort_unless((int) $expectedSellerId === (int) $sellerId, 404);
        $addonSellerId = $menuItem->cafe_id ? $addonMenuItem->cafe_id : $addonMenuItem->roastery_id;
        abort_unless((int) $addonSellerId === (int) $sellerId, 404);
        abort_unless($menuItem->addons()->whereKey($addonMenuItem->id)->exists(), 404);

        $quantity = (int) $validated['quantity'];
        $selectedElsewhere = collect($cart['addons'] ?? [])
            ->reject(fn ($selected, $parentId) => (int) $parentId === (int) $menuItem->id)
            ->sum(fn ($selected) => (int) ($selected[$addonMenuItem->id] ?? 0));
        if ($quantity + $selectedElsewhere > $addonMenuItem->available_stock) {
            return back()->withErrors(['quantity' => 'Jumlah topping tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.']);
        }

        if ($quantity === 0) {
            unset($cart['addons'][$menuItem->id][$addonMenuItem->id]);
            if (empty($cart['addons'][$menuItem->id])) {
                unset($cart['addons'][$menuItem->id]);
            }
        } else {
            $cart['addons'][$menuItem->id][$addonMenuItem->id] = $quantity;
        }

        $request->session()->put('order_cart', $cart);

        return back()->with('status', 'Jumlah topping diperbarui.');
    }

    public function clearCart(Request $request): RedirectResponse
    {
        $request->session()->forget(['order_cart', 'order_checkout_key']);

        return redirect()->route('cart.show')->with('status', 'Keranjang dikosongkan.');
    }

    public function checkout(Cafe $cafe, Request $request): View|RedirectResponse
    {
        return $this->checkoutForShop($cafe, $request);
    }

    public function checkoutRoastery(Roastery $roastery, Request $request): View|RedirectResponse
    {
        return $this->checkoutForShop($roastery, $request);
    }

    private function checkoutForShop(Cafe|Roastery $shop, Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('order_cart', ['cafe_id' => null, 'items' => []]);
        $cartType = $cart['seller_type'] ?? (! empty($cart['cafe_id']) ? 'cafe' : null);
        $cartSellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? 0;
        if ($cartType !== ($shop instanceof Cafe ? 'cafe' : 'roastery') || (int) $cartSellerId !== (int) $shop->id || empty($cart['items'])) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'Keranjang kosong atau bukan dari kedai ini.']);
        }

        $sellerColumn = $shop instanceof Cafe ? 'cafe_id' : 'roastery_id';
        $items = MenuItem::where($sellerColumn, $shop->id)
            ->whereIn('id', array_keys($cart['items']))
            ->whereDoesntHave('addonFor')
            ->get();
        if ($items->count() !== count($cart['items'])) {
            return redirect()->route('cart.show')->withErrors([
                'cart' => 'Keranjang berisi produk yang perlu dipilih ulang. Hapus item tersebut lalu tambahkan kembali dari katalog.',
            ]);
        }
        $addonsByParent = $this->selectedCartAddons($shop, $cart);
        $checkoutKey = $request->session()->get('order_checkout_key');
        if (! $checkoutKey) {
            $checkoutKey = (string) Str::uuid();
            $request->session()->put('order_checkout_key', $checkoutKey);
        }
        $cafe = $shop;
        $paymentQrAvailable = $this->paymentQrIsAvailable($shop);
        $deliveryAvailable = $shop instanceof Cafe
            && $shop->delivery_base_fee !== null
            && $shop->delivery_fee_per_km !== null;
        $whatsappUrl = $this->shopWhatsappUrl($shop, 'Halo, saya membutuhkan bantuan terkait pembayaran pesanan.');

        return view('orders.checkout', compact('cafe', 'cart', 'items', 'addonsByParent', 'checkoutKey', 'paymentQrAvailable', 'deliveryAvailable', 'whatsappUrl'));
    }

    public function placeOrder(Request $request, Cafe $cafe, OrderService $orders): RedirectResponse
    {
        return $this->placeOrderForShop($request, $cafe, $orders);
    }

    public function placeRoasteryOrder(Request $request, Roastery $roastery, OrderService $orders): RedirectResponse
    {
        return $this->placeOrderForShop($request, $roastery, $orders);
    }

    private function placeOrderForShop(Request $request, Cafe|Roastery $shop, OrderService $orders): RedirectResponse
    {
        abort_unless($shop->status === 'published', 404);
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_whatsapp' => ['required', 'string', 'regex:/^[0-9+() .-]+$/', 'min:8', 'max:32'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'checkout_key' => ['nullable', 'uuid'],
            'payment_method' => ['required', 'in:qris,transfer,cash'],
            'fulfillment_method' => $shop instanceof Cafe ? ['sometimes', 'in:pickup,delivery'] : ['prohibited'],
            'delivery_address' => [
                'exclude_unless:fulfillment_method,delivery',
                'required',
                'string',
                'min:8',
                'max:1000',
            ],
        ]);
        $paymentMethod = $validated['payment_method'];
        $fulfillmentMethod = $shop instanceof Cafe ? ($validated['fulfillment_method'] ?? 'pickup') : 'pickup';
        if ($fulfillmentMethod === 'delivery' && $paymentMethod === 'cash') {
            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran tunai di kedai hanya tersedia untuk pesanan ambil sendiri.',
            ]);
        }
        if ($fulfillmentMethod === 'delivery' && (
            ! ($shop instanceof Cafe)
            || $shop->delivery_base_fee === null
            || $shop->delivery_fee_per_km === null
        )) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'Pengantaran belum tersedia karena tarif ongkir kafe belum diatur.',
            ]);
        }

        if ($paymentMethod === 'qris' && ! $this->paymentQrIsAvailable($shop)) {
            throw ValidationException::withMessages(['payment_method' => 'QRIS kedai sedang tidak tersedia. Pilih metode lain atau hubungi kedai melalui WhatsApp.']);
        }

        $cart = $request->session()->get('order_cart', ['cafe_id' => null, 'items' => []]);
        $cartType = $cart['seller_type'] ?? (! empty($cart['cafe_id']) ? 'cafe' : null);
        $cartSellerId = $cart['seller_id'] ?? $cart['cafe_id'] ?? 0;
        if ($cartType !== ($shop instanceof Cafe ? 'cafe' : 'roastery') || (int) $cartSellerId !== (int) $shop->id || empty($cart['items'])) {
            throw ValidationException::withMessages(['cart' => 'Keranjang kosong atau kedai tidak cocok.']);
        }

        $checkoutKey = $request->session()->get('order_checkout_key');
        if (! $checkoutKey) {
            $checkoutKey = (string) Str::uuid();
            $request->session()->put('order_checkout_key', $checkoutKey);
        }
        if (isset($validated['checkout_key']) && ! hash_equals((string) $checkoutKey, $validated['checkout_key'])) {
            throw ValidationException::withMessages(['checkout_key' => 'Sesi checkout berubah. Muat ulang halaman checkout.']);
        }

        $order = $orders->placeOrder(
            $shop,
            $cart['items'],
            $validated['customer_name'],
            $validated['customer_whatsapp'] ?? null,
            $request->user()?->id,
            $validated['customer_note'] ?? null,
            $checkoutKey,
            $paymentMethod,
            null,
            $cart['addons'] ?? [],
            $fulfillmentMethod,
            $validated['delivery_address'] ?? null,
        );

        $request->session()->forget(['order_cart', 'order_checkout_key']);

        return redirect()->route('orders.show', $order->public_code)->with('status', 'Pesanan dibuat. Silakan ikuti petunjuk pembayaran.');
    }

    public function show(Request $request, string $code, OrderService $orders)
    {
        $orders->expireDueOrders();
        $orders->completeUnconfirmedOrders();
        $order = Order::with(['cafe', 'roastery', 'items', 'payments' => fn ($query) => $query->latest('id'), 'statusHistories'])
            ->where('public_code', strtoupper($code))
            ->firstOrFail();
        $canCustomerCancel = $request->user()
            && (int) $order->user_id === (int) $request->user()->id
            && $order->canBeCancelledByCustomer();
        $customer = $request->user('customer');
        $canCustomerConfirmReceipt = $customer && (int) $order->user_id === (int) $customer->id;
        $order->makeHidden(['user_id', 'customer_whatsapp']);
        $shop = $order->shop;
        $paymentQrAvailable = $shop && $this->paymentQrIsAvailable($shop);
        $whatsappUrl = $shop
            ? $this->shopWhatsappUrl($shop, "Halo, saya perlu bantuan pembayaran pesanan {$order->public_code}.")
            : null;
        $orderIssueWhatsappUrl = $shop
            ? $this->shopWhatsappUrl($shop, "Halo, pesanan {$order->public_code} belum saya terima / ada kendala. Mohon bantu periksa.")
            : null;
        $orderIssueContactUrl = $orderIssueWhatsappUrl ?: ($order->cafe
            ? route('cafes.show', $order->cafe->slug)
            : route('roastery.show', $order->roastery->slug));

        return response()->view('orders.show', compact('order', 'paymentQrAvailable', 'whatsappUrl', 'orderIssueWhatsappUrl', 'orderIssueContactUrl', 'canCustomerCancel', 'canCustomerConfirmReceipt'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function cancel(Request $request, string $code, OrderService $orders): RedirectResponse
    {
        $order = Order::query()
            ->where('public_code', strtoupper($code))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $orders->cancelByCustomer($order, $request->user());

        return redirect()->route('orders.show', $order->public_code)
            ->with('status', 'Pesanan dibatalkan dan stok sudah tersedia kembali.');
    }

    public function status(string $code, OrderService $orders)
    {
        $orders->expireDueOrders();
        $orders->completeUnconfirmedOrders();
        $order = Order::where('public_code', strtoupper($code))->firstOrFail();

        return response()->json([
            'code' => $order->public_code,
            'status' => $order->status,
            'label' => $order->status_label,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'payment_expires_at' => $order->payment_expires_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private, max-age=0');
    }

    public function submitPayment(Request $request, string $code, OrderService $orders): RedirectResponse
    {
        $orders->expireDueOrders();
        $orders->completeUnconfirmedOrders();
        $order = Order::with(['cafe', 'roastery'])->where('public_code', strtoupper($code))->firstOrFail();
        abort_unless(in_array($order->payment_method, ['qris', 'transfer'], true), 404);
        if ($order->status !== Order::STATUS_AWAITING_PAYMENT) {
            $message = match ($order->status) {
                Order::STATUS_PENDING_VERIFICATION => 'Bukti pembayaran sudah diterima dan sedang diperiksa kedai. Tidak perlu mengirim ulang.',
                Order::STATUS_CANCELLED => $order->payment_status === 'expired'
                    ? 'Batas waktu pembayaran telah berakhir. Pesanan dibatalkan dan stok dikembalikan.'
                    : 'Pesanan ini sudah dibatalkan.',
                default => 'Pesanan ini tidak sedang menunggu pembayaran.',
            };
            throw ValidationException::withMessages(['payment' => $message]);
        }
        if ($order->payment_method === 'qris' && ! $order->shop?->payment_qr_path) {
            throw ValidationException::withMessages([
                'payment' => 'QRIS kedai sedang diperbarui atau tidak tersedia. Silakan hubungi kedai melalui WhatsApp sebelum membayar.',
            ]);
        }
        if ($order->payment_method === 'qris' && ! $this->paymentQrIsAvailable($order->shop)) {
            throw ValidationException::withMessages([
                'payment' => 'QRIS kedai tidak dapat dibuka saat ini. Silakan hubungi kedai melalui WhatsApp sebelum membayar.',
            ]);
        }
        $validated = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($order->fulfillment_method === 'delivery' && ! $order->delivery_fee_confirmed_at) {
            throw ValidationException::withMessages([
                'payment' => 'Tunggu sampai kafe mengonfirmasi ongkir sebelum mengirim bukti pembayaran.',
            ]);
        }

        $path = $request->file('proof')->store('payment-proofs', 'local');
        if (! $path || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'proof' => 'Bukti pembayaran gagal disimpan. Silakan coba unggah kembali.',
            ]);
        }

        try {
            $orders->submitPayment($order, $path, $order->payment_method);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('orders.show', $order->public_code)->with('status', 'Bukti terkirim. Kedai akan memeriksa pembayaran Anda.');
    }

    public function confirmReceived(Request $request, string $code, OrderService $orders): RedirectResponse
    {
        $order = Order::query()->where('public_code', strtoupper($code))->firstOrFail();
        $customer = $request->user('customer');
        abort_unless($customer && (int) $order->user_id === (int) $customer->id, 404);
        $deadlinePassed = $order->receipt_confirmation_deadline_started_at
            && $order->receipt_confirmation_deadline_started_at->copy()->addHours(24)->lte(now());
        $orders->confirmReceived($order, $customer);

        return redirect()->route('orders.show', $order->public_code)
            ->with(
                'status',
                $deadlinePassed
                    ? 'Batas 24 jam sudah lewat dan sistem menyelesaikan pesanan otomatis.'
                    : 'Penerimaan pesanan dikonfirmasi. Pesanan selesai.',
            );
    }

    public function receipt(string $code, OrderService $orders): Response
    {
        $orders->expireDueOrders();
        $orders->completeUnconfirmedOrders();
        $order = Order::with(['cafe', 'roastery', 'items'])->where('public_code', strtoupper($code))->firstOrFail();
        $paidReceiptAvailable = $order->payment_status === 'paid' && $order->paid_at;
        $cashPickupTicketAvailable = $order->payment_method === 'cash'
            && $order->status === Order::STATUS_AWAITING_PAYMENT
            && $order->payment_expires_at?->isFuture();
        abort_unless($paidReceiptAvailable || $cashPickupTicketAvailable, 404);

        return response()->view('orders.customer-receipt', compact('order'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function history(Request $request): View
    {
        app(OrderService::class)->completeUnconfirmedOrders();
        $orders = Order::with(['cafe', 'roastery', 'items'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('orders.history', compact('orders'));
    }

    private function paymentQrIsAvailable(Cafe|Roastery $shop): bool
    {
        return filled($shop->payment_qr_path)
            && Storage::disk('public')->exists($shop->payment_qr_path);
    }

    private function selectedCartAddons(Cafe|Roastery $shop, array $cart): Collection
    {
        $selections = collect($cart['addons'] ?? []);
        if ($selections->isEmpty()) {
            return collect();
        }

        $sellerColumn = $shop instanceof Cafe ? 'cafe_id' : 'roastery_id';
        $parents = MenuItem::query()
            ->where($sellerColumn, $shop->id)
            ->whereIn('id', $selections->keys()->map(fn ($id) => (int) $id)->all())
            ->with('addons')
            ->get()
            ->keyBy('id');

        return $selections->mapWithKeys(function ($selectedAddons, $parentId) use ($parents, $sellerColumn, $shop): array {
            $parent = $parents->get((int) $parentId);
            if (! $parent) {
                return [];
            }

            $allowedAddons = $parent->addons
                ->where($sellerColumn, $shop->id)
                ->keyBy('id');
            $items = collect($selectedAddons)
                ->map(function ($quantity, $addonId) use ($allowedAddons): ?MenuItem {
                    $addon = $allowedAddons->get((int) $addonId);
                    if (! $addon || (int) $quantity < 1) {
                        return null;
                    }

                    $addon->cart_quantity = (int) $quantity;

                    return $addon;
                })
                ->filter()
                ->values();

            return $items->isEmpty() ? [] : [(int) $parentId => $items];
        });
    }

    private function shopWhatsappUrl(Cafe|Roastery $shop, string $message): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $shop->whatsapp_number);
        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (! str_starts_with($number, '62')) {
            $number = '62'.$number;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
