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
        $additionalItems = collect();

        if ($cafe && ! empty($cart['items'])) {
            $sellerColumn = $cafe instanceof Cafe ? 'cafe_id' : 'roastery_id';
            $shopItems = MenuItem::query()->where($sellerColumn, $cafe->id);
            $items = (clone $shopItems)
                ->whereIn('id', array_keys($cart['items']))
                ->get()
                ->map(function (MenuItem $item) use ($cart) {
                    $item->cart_quantity = (int) ($cart['items'][$item->id] ?? 0);

                    return $item;
                });
            $additionalItems = $shopItems
                ->whereNotIn('id', array_keys($cart['items']))
                ->where('is_active', true)
                ->whereRaw('stock > reserved_stock')
                ->orderBy('name')
                ->get();
        }

        return view('orders.cart', compact('cart', 'cafe', 'items', 'additionalItems'));
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
        if ($available <= $current) {
            $message = 'Menu ini sedang tidak tersedia untuk ditambahkan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['cart' => 'Menu ini sedang tidak tersedia untuk ditambahkan.']);
        }

        $cart['seller_type'] = $shop instanceof Cafe ? 'cafe' : 'roastery';
        $cart['seller_id'] = $shop->id;
        $cart['cafe_id'] = $shop instanceof Cafe ? $shop->id : null;
        $cart['items'][$menuItem->id] = $current + 1;
        $request->session()->put('order_cart', $cart);

        if ($request->expectsJson()) {
            $cartItems = MenuItem::query()
                ->where($sellerColumn, $shop->id)
                ->whereIn('id', array_keys($cart['items']))
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

            return response()->json([
                'message' => 'Menu ditambahkan ke keranjang.',
                'added_item_id' => $menuItem->id,
                'shop_name' => $shop->name,
                'items' => $cartItems,
                'cart_count' => collect($cart['items'])->sum(fn ($quantity) => max(0, (int) $quantity)),
                'total' => $cartItems->sum('line_total'),
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

        $quantity = (int) $validated['quantity'];
        $available = max(0, (int) $menuItem->stock - (int) $menuItem->reserved_stock);
        if ($quantity > $available) {
            return back()->withErrors(['quantity' => 'Jumlah pesanan tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.']);
        }

        if ($quantity === 0) {
            unset($cart['items'][$menuItem->id]);
        } else {
            $cart['items'][$menuItem->id] = $quantity;
        }
        if (empty($cart['items'])) {
            $cart = ['seller_type' => null, 'seller_id' => null, 'cafe_id' => null, 'items' => []];
        }
        $request->session()->put('order_cart', $cart);

        return back()->with('status', 'Keranjang diperbarui.');
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
        $items = MenuItem::where($sellerColumn, $shop->id)->whereIn('id', array_keys($cart['items']))->get();
        $checkoutKey = $request->session()->get('order_checkout_key');
        if (! $checkoutKey) {
            $checkoutKey = (string) Str::uuid();
            $request->session()->put('order_checkout_key', $checkoutKey);
        }
        $cafe = $shop;
        $paymentQrAvailable = $this->paymentQrIsAvailable($shop);
        $whatsappUrl = $this->shopWhatsappUrl($shop, 'Halo, saya membutuhkan bantuan terkait pembayaran pesanan.');

        return view('orders.checkout', compact('cafe', 'cart', 'items', 'checkoutKey', 'paymentQrAvailable', 'whatsappUrl'));
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
            'customer_whatsapp' => [auth()->check() ? 'nullable' : 'required', 'string', 'regex:/^[0-9+() .-]+$/', 'min:8', 'max:32'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'checkout_key' => ['nullable', 'uuid'],
            'payment_method' => ['required', 'in:qris,transfer,cash'],
        ]);
        $paymentMethod = $validated['payment_method'];
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
        );

        $request->session()->forget(['order_cart', 'order_checkout_key']);

        return redirect()->route('orders.show', $order->public_code)->with('status', 'Pesanan dibuat. Silakan ikuti petunjuk pembayaran.');
    }

    public function lookupForm(): View
    {
        return view('orders.lookup');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'alpha_num', 'min:8', 'max:20']]);
        $order = Order::where('public_code', strtoupper($validated['code']))->first();
        if (! $order) {
            return back()->withErrors(['code' => 'Kode pesanan tidak dikenal.']);
        }

        return redirect()->route('orders.show', $order->public_code);
    }

    public function show(string $code)
    {
        $order = Order::with(['cafe', 'roastery', 'items', 'payments' => fn ($query) => $query->latest('id'), 'statusHistories'])
            ->where('public_code', strtoupper($code))
            ->firstOrFail();
        $order->makeHidden(['user_id', 'customer_whatsapp']);
        $shop = $order->shop;
        $paymentQrAvailable = $shop && $this->paymentQrIsAvailable($shop);
        $whatsappUrl = $shop
            ? $this->shopWhatsappUrl($shop, "Halo, saya perlu bantuan pembayaran pesanan {$order->public_code}.")
            : null;
        return response()->view('orders.show', compact('order', 'paymentQrAvailable', 'whatsappUrl'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function status(string $code)
    {
        $order = Order::where('public_code', strtoupper($code))->firstOrFail();

        return response()->json([
            'code' => $order->public_code,
            'status' => $order->status,
            'label' => $order->status_label,
            'payment_status' => $order->payment_status,
            'updated_at' => $order->updated_at?->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private, max-age=0');
    }

    public function submitPayment(Request $request, string $code, OrderService $orders): RedirectResponse
    {
        $order = Order::with(['cafe', 'roastery'])->where('public_code', strtoupper($code))->firstOrFail();
        abort_unless(in_array($order->payment_method, ['qris', 'transfer'], true), 404);
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
            'reference' => ['required', 'string', 'min:4', 'max:120', 'unique:order_payments,reference'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ((int) round(((float) $validated['amount']) * 100) !== (int) round(((float) $order->total) * 100)) {
            return back()->withErrors(['amount' => 'Nominal yang dilaporkan harus sama dengan total pesanan.'])->withInput();
        }

        $path = $request->file('proof')->store('payment-proofs', 'local');
        try {
            $orders->submitPayment($order, $validated['reference'], (string) $validated['amount'], $path, $order->payment_method);
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('orders.show', $order->public_code)->with('status', 'Bukti terkirim. Kedai akan memeriksa pembayaran Anda.');
    }

    public function receipt(string $code): Response
    {
        $order = Order::with(['cafe', 'roastery', 'items'])->where('public_code', strtoupper($code))->firstOrFail();

        return response()->view('orders.customer-receipt', compact('order'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function history(Request $request): View
    {
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
