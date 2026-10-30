<?php

namespace Tests\Feature\Ordering;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceiptPrintJob;
use App\Models\Roastery;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderingUatTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Cafe $cafe;

    private MenuItem $menuItem;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->owner = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'user']);
        $this->cafe = Cafe::factory()->create(['owner_id' => $this->owner->id, 'status' => 'published']);
        $this->menuItem = MenuItem::create([
            'cafe_id' => $this->cafe->id,
            'name' => 'Kopi Susu UAT',
            'description' => 'Minuman uji',
            'price' => '25000.00',
            'stock' => 5,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_customer_checkout_reserves_stock_and_verified_payment_consumes_it_once(): void
    {
        $this->actingAs($this->customer)
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 2]]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT',
                'customer_whatsapp' => '081234567890',
            ])
            ->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame($this->customer->id, $order->user_id);
        $this->assertSame('6281234567890', $order->customer_whatsapp);
        $this->assertNotEmpty($order->idempotency_key);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame('25000.00', $order->items->first()->unit_price);
        $this->assertSame('50000.00', $order->total);
        $this->assertSame(5, $this->menuItem->fresh()->stock, 'Physical stock is not consumed before verification.');
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);

        Storage::fake('local');
        $this->post(route('orders.payment.submit', $order->public_code), [
            'reference' => 'UAT-TRANSFER-0001',
            'amount' => '50000.00',
            'proof' => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('orders.show', $order->public_code));

        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $order->fresh()->status);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->post(route('orders.payment.submit', $order->public_code), [
            'reference' => 'UAT-TRANSFER-0001',
            'amount' => '50000.00',
            'proof' => UploadedFile::fake()->create('duplicate.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('reference');
        $this->assertDatabaseCount('order_payments', 1);

        $this->actingAs($this->owner)
            ->post(route('owner.orders.confirm-payment', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(3, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(1, ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->count());
        $this->assertSame('pending', ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->firstOrFail()->state);
        $this->assertSame('confirmed', OrderPayment::where('order_id', $order->id)->firstOrFail()->status);

        $this->post(route('owner.orders.reprint', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status, 'Reprinting must not change payment/order status.');
        $this->assertSame(1, ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'reprint')->count());

        $this->post(route('owner.orders.confirm-payment', $order->public_code))->assertSessionHasErrors('payment');
        $this->assertSame(3, $this->menuItem->fresh()->stock, 'A duplicate confirmation must not decrement inventory again.');
        $this->assertSame(1, ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->count());
    }

    public function test_checkout_requires_customer_login_and_returns_to_checkout_after_login(): void
    {
        $checkout = route('checkout.show', $this->cafe->slug);

        $this->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]]])
            ->get($checkout)
            ->assertRedirect(route('login'));

        $this->assertSame($checkout, session()->get('url.intended'));

        $this->post(route('login.store'), [
            'email' => $this->customer->email,
            'password' => 'password',
        ])->assertRedirect($checkout);
        $this->assertAuthenticatedAs($this->customer);
        $this->get($checkout)->assertOk();
    }

    public function test_customer_registration_always_creates_customer_role_and_account_contains_shortcuts(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Customer Baru',
            'email' => 'pelanggan-baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('account'));

        $customer = User::where('email', 'pelanggan-baru@example.test')->firstOrFail();
        $this->assertSame('user', $customer->role);
        $this->assertAuthenticatedAs($customer);

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Riwayat pesanan')
            ->assertSee('Keranjang pesanan')
            ->assertSee('Lacak pesanan')
            ->assertSee('Tempat tersimpan')
            ->assertSee('Keluar dari akun');
    }

    public function test_public_cafe_catalog_hides_items_without_available_stock(): void
    {
        $this->menuItem->update(['stock' => 0]);

        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertDontSee('Kopi Susu UAT')
            ->assertDontSee('Rp25.000')
            ->assertDontSee('Tersedia')
            ->assertDontSee('Stok habis');
    }

    public function test_public_cafe_catalog_does_not_disclose_available_stock(): void
    {
        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertDontSee('Tersedia 5')
            ->assertDontSee('Stok habis');
    }

    public function test_cart_does_not_disclose_stock_counts_or_stock_messages(): void
    {
        $this->withSession([
            'order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]],
        ])->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertDontSee('tersedia 5')
            ->assertDontSee('Stok final');
    }

    public function test_repeated_checkout_with_same_key_reuses_order_and_reserves_stock_once(): void
    {
        $key = (string) Str::uuid();
        $service = app(OrderService::class);
        $first = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest UAT', '081234567890', null, null, $key);
        $retry = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest UAT', '081234567890', null, null, $key);

        $this->assertSame($first->id, $retry->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
    }

    public function test_empty_cart_and_overstock_checkout_do_not_create_orders_or_change_stock(): void
    {
        $this->actingAs($this->customer)
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => []]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890',
            ])
            ->assertSessionHasErrors('cart');

        $this->actingAs($this->customer)
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 6]]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890',
            ])
            ->assertSessionHasErrors([
                'cart' => 'Jumlah pesanan tidak dapat dipenuhi. Silakan sesuaikan jumlahnya.',
            ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
    }

    public function test_authenticated_buyer_history_contains_snapshot_items_and_total(): void
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $secondItem = MenuItem::create([
            'cafe_id' => $this->cafe->id, 'name' => 'Roti UAT', 'price' => '12500.00',
            'stock' => 4, 'reserved_stock' => 0, 'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [
                $this->menuItem->id => 1,
                $secondItem->id => 2,
            ]]])
            ->post(route('checkout.store', $this->cafe->slug), ['customer_name' => 'Akun UAT'])
            ->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame($buyer->id, $order->user_id);
        $this->assertSame('50000.00', $order->total);
        $this->get(route('orders.history'))
            ->assertOk()
            ->assertSee($order->public_code)
            ->assertSee('Kopi Susu UAT × 1')
            ->assertSee('Roti UAT × 2')
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_unpaid_order_cannot_be_prepared_and_does_not_decrement_stock(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Guest UAT',
            '628123456789',
            null,
        );

        $this->actingAs($this->owner)
            ->post(route('owner.orders.status', $order->public_code), ['status' => 'preparing'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertDatabaseCount('receipt_print_jobs', 0);
    }

    public function test_order_owner_cannot_access_another_cafe_order_and_guest_can_track_with_code(): void
    {
        $otherOwner = User::factory()->create(['role' => 'admin']);
        $otherCafe = Cafe::factory()->create(['owner_id' => $otherOwner->id, 'status' => 'published']);
        $otherMenu = MenuItem::create([
            'cafe_id' => $otherCafe->id, 'name' => 'Menu B', 'price' => '10000.00',
            'stock' => 2, 'reserved_stock' => 0, 'is_active' => true,
        ]);
        $order = app(OrderService::class)->placeOrder($otherCafe, [$otherMenu->id => 1], 'Guest B', null, null);

        $this->actingAs($this->owner)
            ->get(route('owner.orders.show', $order->public_code))
            ->assertNotFound();

        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee($order->public_code)
            ->assertSee('Menunggu Pembayaran');

        $this->get(route('orders.status', $order->public_code))
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_AWAITING_PAYMENT);
    }

    public function test_pickup_code_completes_only_a_ready_order_once(): void
    {
        $service = app(OrderService::class);
        $order = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest Pickup', null, null);
        $service->submitPayment($order, 'UAT-PICKUP-0001', '25000.00', 'payment-proofs/test.pdf');
        $order = $service->confirmPayment($order->fresh(), $this->owner);

        $this->actingAs($this->owner)
            ->post(route('owner.orders.pickup'), ['code' => $order->public_code])
            ->assertSessionHasErrors('code');
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);

        $printJob = ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->firstOrFail();
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'preparing'])
            ->assertSessionHasErrors('status');
        $this->post(route('owner.print-jobs.status', $printJob), [
            'state' => 'failed', 'last_error' => 'Printer offline',
        ])->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertSame('failed', $printJob->fresh()->state);
        $this->assertSame($this->owner->id, $printJob->fresh()->reported_by);
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'preparing'])
            ->assertSessionHasErrors('status');
        $this->post(route('owner.print-jobs.status', $printJob), ['state' => 'printed'])
            ->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertNotNull($printJob->fresh()->printed_at);
        $this->assertNotNull($printJob->fresh()->reported_at);
        $attemptsAfterPrint = $printJob->fresh()->attempts;
        $printedAt = $printJob->fresh()->printed_at->toIso8601String();
        $this->post(route('owner.print-jobs.status', $printJob), ['state' => 'printed'])
            ->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertSame($attemptsAfterPrint, $printJob->fresh()->attempts);
        $this->assertSame($printedAt, $printJob->fresh()->printed_at->toIso8601String());
        $this->post(route('owner.print-jobs.status', $printJob), [
            'state' => 'failed', 'last_error' => 'late error report',
        ])->assertSessionHasErrors('state');
        $this->assertSame('printed', $printJob->fresh()->state);

        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'preparing'])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_PREPARING, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->preparing_at);
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'ready'])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_READY, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->ready_at);

        $this->post(route('owner.orders.pickup'), ['code' => $order->public_code])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => Order::STATUS_READY,
            'to_status' => Order::STATUS_COMPLETED,
        ]);

        $this->post(route('owner.orders.pickup'), ['code' => $order->public_code])
            ->assertSessionHasErrors('code');
    }

    public function test_roastery_catalog_hides_empty_stock_and_accepts_cash_orders(): void
    {
        $roastery = Roastery::factory()->create(['owner_id' => $this->owner->id, 'status' => 'published']);
        $product = MenuItem::create([
            'roastery_id' => $roastery->id,
            'name' => 'Beans Roastery UAT',
            'category' => 'Biji Kopi',
            'price' => '45000.00',
            'stock' => 0,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->get(route('roastery.show', $roastery->slug))
            ->assertOk()
            ->assertDontSee('Katalog Produk Roastery')
            ->assertDontSee('Beans Roastery UAT');

        $product->update(['stock' => 3]);
        $this->get(route('roastery.show', $roastery->slug))
            ->assertOk()
            ->assertSee('Katalog Produk Roastery')
            ->assertSee('Beans Roastery UAT');

        $this->post(route('roastery.cart.items.add', [$roastery->slug, $product->id]))
            ->assertRedirect(route('cart.show'));
        $this->actingAs($this->customer)->get(route('roastery.checkout.show', $roastery->slug))->assertOk();
        $this->actingAs($this->customer)->post(route('roastery.checkout.store', $roastery->slug), [
            'customer_name' => 'Pembeli Roastery',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'cash',
        ])->assertRedirect();

        $order = Order::where('roastery_id', $roastery->id)->firstOrFail();
        $this->assertNull($order->cafe_id);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame(1, $product->fresh()->reserved_stock);
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Bayar tunai di '.$roastery->name)
            ->assertSee($order->public_code);
        $this->get(route('orders.receipt', $order->public_code))->assertOk()->assertSee('Cetak / Simpan PDF');

        $this->actingAs($this->owner)
            ->post(route('owner.orders.confirm-cash-payment', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'status' => 'confirmed',
        ]);
    }

    public function test_customer_feedback_requires_contact_and_is_managed_in_panel(): void
    {
        $this->get(route('feedback.create'))
            ->assertOk()
            ->assertSee('Keluhan, kritik, dan saran')
            ->assertSee('Kontak yang bisa dihubungi');

        $this->post(route('feedback.store'), [
            'shop' => 'cafe:'.$this->cafe->id,
            'category' => 'suggestion',
            'customer_name' => 'Pelanggan UAT',
            'customer_contact' => '081234567890',
            'message' => 'Mohon sediakan pilihan susu oat.',
        ])->assertRedirect(route('feedback.create'));

        $this->assertDatabaseHas('customer_feedback', [
            'cafe_id' => $this->cafe->id,
            'roastery_id' => null,
            'customer_contact' => '081234567890',
            'status' => 'new',
        ]);

        $this->from(route('feedback.create'))->post(route('feedback.store'), [
            'shop' => 'cafe:'.$this->cafe->id,
            'category' => 'complaint',
            'customer_name' => 'Pelanggan UAT',
            'message' => 'Kontak tidak disertakan.',
        ])->assertSessionHasErrors('customer_contact');
    }

    public function test_qris_and_transfer_remain_manual_and_do_not_call_bank_services(): void
    {
        $this->cafe->update([
            'payment_qr_path' => 'cafes/payment-qr/static-cafe.png',
            'payment_instructions' => 'Gunakan QRIS statis kedai dan tunggu verifikasi staf.',
        ]);
        $cart = ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]];

        $this->actingAs($this->customer)->withSession(['order_cart' => $cart])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('Transfer manual')
            ->assertSee('Tunai saat di kedai')
            ->assertSee('tidak terhubung ke bank');

        $this->actingAs($this->customer)->post(route('checkout.store', $this->cafe->slug), [
            'customer_name' => 'Pembeli Transfer',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
        ])->assertRedirect();
        $transferOrder = Order::where('payment_method', 'transfer')->firstOrFail();
        $this->get(route('orders.show', $transferOrder->public_code))
            ->assertOk()
            ->assertSee('Pembayaran transfer manual')
            ->assertSee('Gunakan QRIS statis kedai');

        Storage::fake('local');
        $this->post(route('orders.payment.submit', $transferOrder->public_code), [
            'reference' => 'MANUAL-TRANSFER-01',
            'amount' => '25000.00',
            'proof' => UploadedFile::fake()->create('transfer.png', 10, 'image/png'),
        ])->assertRedirect(route('orders.show', $transferOrder->public_code));
        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $transferOrder->fresh()->status);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $transferOrder->id,
            'payment_method' => 'transfer',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->customer)->withSession(['order_cart' => $cart])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli QRIS',
                'customer_whatsapp' => '081234567890',
                'payment_method' => 'qris',
            ])->assertRedirect();
        $qrisOrder = Order::where('payment_method', 'qris')->firstOrFail();
        $this->get(route('orders.show', $qrisOrder->public_code))
            ->assertOk()
            ->assertSee('static-cafe.png')
            ->assertSee('Pembayaran QRIS');
    }
}
