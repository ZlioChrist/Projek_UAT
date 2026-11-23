<?php

namespace Tests\Feature\Ordering;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\ReceiptPrintJob;
use App\Models\Roastery;
use App\Models\User;
use App\Filament\Resources\OrderReportResource;
use App\Filament\Resources\OrderResource;
use App\Notifications\OrderPaymentVerifiedNotification;
use App\Notifications\OrderReadyNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 2]]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT',
                'customer_whatsapp' => '081234567890',
                'payment_method' => 'transfer',
            ])
            ->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame($this->customer->id, $order->user_id);
        $this->assertSame('6281234567890', $order->customer_whatsapp);
        $this->assertMatchesRegularExpression('/^WN-\d{4}$/', $order->display_code);
        $this->assertNotEmpty($order->idempotency_key);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame('25000.00', $order->items->first()->unit_price);
        $this->assertSame('50000.00', $order->total);
        $this->assertNull($order->sender_bank);
        $this->assertNotNull($order->payment_expires_at);
        $this->assertSame(5, $this->menuItem->fresh()->stock, 'Physical stock is not consumed before verification.');
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Cukup unggah bukti pembayaran')
            ->assertDontSee('name="reference"', false)
            ->assertSee($order->display_code);

        Storage::fake('local');
        $this->post(route('orders.payment.submit', $order->public_code), [
            'proof' => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('orders.show', $order->public_code));

        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $order->fresh()->status);
        $this->assertNull($order->payments()->firstOrFail()->reference);
        $this->assertSame('50000.00', $order->payments()->firstOrFail()->amount);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->post(route('orders.payment.submit', $order->public_code), [
            'proof' => UploadedFile::fake()->create('duplicate.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors([
            'payment' => 'Bukti pembayaran sudah diterima dan sedang diperiksa kedai. Tidak perlu mengirim ulang.',
        ]);
        $this->assertDatabaseCount('order_payments', 1);
        $this->travel(20)->minutes();
        $this->assertSame(0, app(OrderService::class)->expireDueOrders(), 'Submitted payments stay reserved while the cafe reviews them.');
        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $order->fresh()->status);

        $this->actingAs($this->owner, 'web')
            ->get(route('owner.orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('owner-payment-review', false)
            ->assertSee('owner-order-action--confirm', false)
            ->assertSee('owner-order-action--cancel', false);

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.confirm-payment', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(3, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(1, ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->count());
        $this->get(route('orders.receipt', $order->public_code))
            ->assertOk()
            ->assertSee('receipt-paper', false)
            ->assertSee('Rincian pesanan');

        $this->post(route('owner.orders.confirm-payment', $order->public_code))
            ->assertSessionHasErrors('payment');
        $this->assertSame(3, $this->menuItem->fresh()->stock, 'A duplicate confirmation must not decrement inventory again.');
        $this->assertSame(1, ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->count());
    }

    public function test_transfer_checkout_does_not_require_a_sender_bank_selection(): void
    {
        $cart = ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]];
        $payload = [
            'customer_name' => 'Pembeli Transfer',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
        ];

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => $cart])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertDontSee('name="sender_bank"', false)
            ->assertDontSee('Transfer dari bank apa?');

        $this->post(route('checkout.store', $this->cafe->slug), $payload)
            ->assertRedirect();

        $order = Order::where('payment_method', 'transfer')->firstOrFail();
        $this->assertNull($order->sender_bank);
        $this->assertSame('25000.00', $order->total, 'Transfer bank fees are paid separately and are not added to the order total.');
    }

    public function test_delivery_fee_is_added_before_proof_only_payment_is_submitted(): void
    {
        Storage::fake('local');
        $this->cafe->update([
            'delivery_base_fee' => 10000,
            'delivery_fee_per_km' => 3000,
        ]);
        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]]])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Cara menerima pesanan')
            ->assertSee('Alamat pengantaran')
            ->assertSee('jarak km dibulatkan ke atas');

        $this->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli Antar',
                'customer_whatsapp' => '081234567890',
                'payment_method' => 'transfer',
                'fulfillment_method' => 'delivery',
                'delivery_address' => 'Jl. Kopi Nomor 10, Samarinda',
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('delivery', $order->fulfillment_method);
        $this->assertNull($order->delivery_fee_confirmed_at);
        $this->assertSame('25000.00', $order->total);

        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Menunggu kesepakatan ongkir');
        $this->post(route('orders.payment.submit', $order->public_code), [
            'proof' => UploadedFile::fake()->create('before-fee.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('order_payments', 0);

        $this->actingAs($this->owner, 'web')
            ->get(route('owner.orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Hitung ongkir sebelum pembayaran')
            ->assertSee('Ukur rute di Google Maps')
            ->assertSee('Rp10.000')
            ->assertSee('name="distance_km"', false);

        $this->post(route('owner.orders.delivery-fee', $order->public_code), ['distance_km' => '0'])
            ->assertSessionHasErrors('distance_km');
        $this->post(route('owner.orders.delivery-fee', $order->public_code), ['distance_km' => '4.2'])
            ->assertRedirect(route('owner.orders.show', $order->public_code));

        $this->assertSame('50000.00', $order->fresh()->total);
        $this->assertSame('25000.00', $order->fresh()->delivery_fee);
        $this->assertSame('4.20', $order->fresh()->delivery_distance_km);
        $this->assertNotNull($order->fresh()->delivery_fee_confirmed_at);
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Jarak rute: 4,20 km (dibulatkan ke 5 km).');
        $this->get(route('orders.status', $order->public_code))
            ->assertJsonPath('total', '50000.00');

        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.payment.submit', $order->public_code), [
                'proof' => UploadedFile::fake()->create('after-fee.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('orders.show', $order->public_code));

        $payment = $order->payments()->firstOrFail();
        $this->assertNull($payment->reference);
        $this->assertSame('50000.00', $payment->amount);
    }

    public function test_delivery_is_unavailable_until_cafe_configures_both_tariffs(): void
    {
        $payload = [
            'customer_name' => 'Pembeli Antar',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
            'fulfillment_method' => 'delivery',
            'delivery_address' => 'Jl. Kopi Nomor 10, Samarinda',
        ];

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]]])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Pengantaran belum tersedia')
            ->assertDontSee('name="fulfillment_method" value="delivery"', false);

        $this->post(route('checkout.store', $this->cafe->slug), $payload)
            ->assertSessionHasErrors('fulfillment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_qris_payment_cannot_be_submitted_without_proof_and_rejection_cancels_order(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cafes/payment-qr/qris-uat.png', 'fake-qris');
        $this->cafe->update(['payment_qr_path' => 'cafes/payment-qr/qris-uat.png']);

        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli QRIS',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'qris',
        );
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Bayar tepat')
            ->assertSee('QRIS ini dikelola kedai')
            ->assertSee('payment-countdown');
        Storage::fake('local');

        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.payment.submit', $order->public_code), [])
            ->assertSessionHasErrors('proof');

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertDatabaseCount('order_payments', 0);
        $this->get(route('orders.receipt', $order->public_code))->assertNotFound();

        $this->post(route('orders.payment.submit', $order->public_code), [
            'proof' => UploadedFile::fake()->create('qris-proof.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('orders.show', $order->public_code));

        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $order->fresh()->status);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'status' => 'submitted',
        ]);
        $this->assertSame(0, ReceiptPrintJob::where('order_id', $order->id)->count());

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.reject-payment', $order->public_code), [
                'reason' => 'Dana QRIS belum diterima.',
            ])
            ->assertRedirect(route('owner.orders.show', $order->public_code));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->payment_status);
        $this->assertSame('rejected', $order->payments()->firstOrFail()->status);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(0, ReceiptPrintJob::where('order_id', $order->id)->count());
        $this->get(route('orders.receipt', $order->public_code))->assertNotFound();
    }

    public function test_order_displays_the_static_qris_uploaded_by_its_own_cafe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cafes/payment-qr/first-cafe.png', 'first-qris');
        Storage::disk('public')->put('cafes/payment-qr/second-cafe.png', 'second-qris');
        $this->cafe->update(['payment_qr_path' => 'cafes/payment-qr/first-cafe.png']);

        $secondCafe = Cafe::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'published',
            'payment_qr_path' => 'cafes/payment-qr/second-cafe.png',
        ]);
        $secondMenuItem = MenuItem::create([
            'cafe_id' => $secondCafe->id,
            'name' => 'Menu Kedai Kedua',
            'price' => '30000.00',
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]]])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kode QRIS akan ditampilkan di halaman menunggu pembayaran')
            ->assertDontSee('first-cafe.png');

        $firstOrder = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli Kedai Pertama',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'qris',
        );
        $secondOrder = app(OrderService::class)->placeOrder(
            $secondCafe,
            [$secondMenuItem->id => 1],
            'Pembeli Kedai Kedua',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'qris',
        );

        $this->get(route('orders.show', $firstOrder->public_code))
            ->assertOk()
            ->assertSee('first-cafe.png')
            ->assertDontSee('second-cafe.png');
        $this->get(route('orders.show', $secondOrder->public_code))
            ->assertOk()
            ->assertSee('second-cafe.png')
            ->assertDontSee('first-cafe.png');
    }

    public function test_unpaid_orders_expire_and_release_reserved_stock_without_consuming_stock(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 2],
            'Pembeli Kedaluwarsa',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'transfer',
            'bca',
        );
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);

        $this->travel(16)->minutes();
        $this->assertSame(1, app(OrderService::class)->expireDueOrders());

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('expired', $order->payment_status);
        $this->assertNull($order->payment_expires_at);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(0, ReceiptPrintJob::where('order_id', $order->id)->count());
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Batas waktu pembayaran berakhir');
        $this->get(route('orders.receipt', $order->public_code))->assertNotFound();
        $this->assertSame(0, app(OrderService::class)->expireDueOrders(), 'Expiration is idempotent.');
    }

    public function test_cash_order_reservation_expires_after_thirty_minutes_and_cannot_be_confirmed_late(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 2],
            'Pembeli Tunai',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'cash',
        );

        $this->assertEqualsWithDelta(
            30,
            $order->created_at->diffInSeconds($order->payment_expires_at) / 60,
            1,
        );
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);

        $this->travel(16)->minutes();
        $this->assertSame(
            0,
            app(OrderService::class)->expireDueOrders(),
            'Cash reservations do not use the 15-minute QRIS/transfer payment deadline.',
        );
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);

        $this->travel(15)->minutes();
        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.confirm-cash-payment', $order->public_code))
            ->assertSessionHasErrors('payment');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_owner_receipt_only_opens_print_dialog_after_explicit_action(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli Cetak UAT',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'cash',
        );
        app(OrderService::class)->confirmCashPayment($order, $this->owner);

        $this->actingAs($this->owner, 'web')
            ->get(route('owner.orders.print', $order->public_code))
            ->assertOk()
            ->assertSee($this->cafe->name)
            ->assertSee($this->menuItem->name)
            ->assertSee('Rp25.000')
            ->assertSee($order->display_code)
            ->assertSee('receipt-paper', false)
            ->assertSee('Rincian pesanan')
            ->assertSee('Lunas')
            ->assertSee('receipt-button--confirm', false)
            ->assertSee('onclick="window.print()"', false)
            ->assertDontSee("window.addEventListener('load'", false)
            ->assertDontSee('setTimeout', false);
    }

    public function test_customer_can_cancel_own_unpaid_order_but_not_another_customers_order(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 2],
            'Pembeli UAT',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'cash',
        );
        $otherCustomer = User::factory()->create(['role' => 'user']);

        $this->actingAs($otherCustomer, 'customer')
            ->post(route('orders.cancel', $order->public_code))
            ->assertNotFound();

        $this->actingAs($this->customer, 'customer')
            ->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Batalkan pesanan');

        $this->post(route('orders.cancel', $order->public_code))
            ->assertRedirect(route('orders.show', $order->public_code));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->payment_status);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => Order::STATUS_CANCELLED,
            'note' => 'Pesanan dibatalkan oleh customer sebelum pembayaran.',
        ]);
    }

    public function test_customer_cannot_cancel_after_submitting_payment_proof(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli UAT',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'transfer',
            'bca',
        );
        Storage::fake('local');

        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.payment.submit', $order->public_code), [
                'proof' => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('orders.show', $order->public_code));

        $this->post(route('orders.cancel', $order->public_code))
            ->assertSessionHasErrors('order');

        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $order->fresh()->status);
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame('submitted', $order->payments()->firstOrFail()->status);
    }

    public function test_late_payment_proof_expires_order_instead_of_accepting_payment(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli Terlambat',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'qris',
        );
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/late.png', 'proof');
        $this->travel(16)->minutes();

        try {
            app(OrderService::class)->submitPayment($order, 'payment-proofs/late.png', 'qris');
            $this->fail('An expired payment must not be accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame(0, $order->payments()->count());
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertNotNull($order->fresh()->cancelled_at);
        $this->assertSame(0, ReceiptPrintJob::where('order_id', $order->id)->count());
        $this->actingAs($this->owner, 'web')
            ->get(route('owner.orders.print', $order->public_code))
            ->assertNotFound();
        $this->get(route('orders.receipt', $order->public_code))->assertNotFound();
    }

    public function test_adding_a_menu_item_can_return_json_for_the_in_page_cart_popup(): void
    {
        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['seller_type' => null, 'seller_id' => null, 'items' => []]])
            ->postJson(route('cart.items.add', [$this->cafe->slug, $this->menuItem->id]))
            ->assertOk()
            ->assertJson([
                'message' => 'Menu ditambahkan ke keranjang.',
                'added_item_id' => $this->menuItem->id,
                'shop_name' => $this->cafe->name,
                'cart_count' => 1,
                'total' => 25000,
            ]);
        $this->assertSame(1, (int) session('order_cart.items.'.$this->menuItem->id));
    }

    public function test_customer_login_is_required_before_a_product_can_be_added_to_cart(): void
    {
        $this->postJson(route('cart.items.add', [$this->cafe->slug, $this->menuItem->id]))
            ->assertUnauthorized();

        $this->assertSame([], session('order_cart.items', []));

        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('data-customer-authenticated="false"', false);
    }

    public function test_increasing_stock_reactivates_an_inactive_menu_item_when_stock_is_available(): void
    {
        $this->menuItem->update([
            'stock' => 0,
            'is_active' => false,
        ]);

        $this->menuItem->update(['stock' => 3]);

        $this->assertTrue($this->menuItem->fresh()->is_active);
    }

    public function test_decreasing_stock_does_not_reactivate_an_inactive_menu_item(): void
    {
        $this->menuItem->update([
            'is_active' => false,
        ]);

        $this->menuItem->update(['stock' => 4]);

        $this->assertFalse($this->menuItem->fresh()->is_active);
    }

    public function test_stock_cannot_be_set_below_active_reservations(): void
    {
        $this->menuItem->update(['reserved_stock' => 3]);

        try {
            $this->menuItem->update(['stock' => 2]);
            $this->fail('Physical stock must not be reduced below active reservations.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stock', $exception->errors());
        }

        $this->assertSame(5, $this->menuItem->fresh()->stock);
        $this->assertSame(3, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(2, $this->menuItem->fresh()->available_stock);

        $this->menuItem->update(['stock' => 3]);
        $this->assertSame(0, $this->menuItem->fresh()->available_stock);
    }

    public function test_reserved_stock_cannot_be_set_to_a_negative_value(): void
    {
        try {
            $this->menuItem->update(['reserved_stock' => -1]);
            $this->fail('Reserved stock must not be negative.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reserved_stock', $exception->errors());
        }

        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
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
        $this->assertAuthenticatedAs($this->customer, 'customer');
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
        $this->assertAuthenticatedAs($customer, 'customer');

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Riwayat pesanan')
            ->assertSee('Keranjang pesanan')
            ->assertDontSee('Lacak pesanan')
            ->assertSee('Favorit')
            ->assertSee('Kritik & saran')
            ->assertSee('Atur profil')
            ->assertSee('Ruang pelanggan')
            ->assertDontSee('Simpan profil')
            ->assertDontSee('Daftarkan kafe / roastery')
            ->assertSee('Keluar dari akun');

        $this->get(route('account.profile.edit'))->assertOk()->assertSee('Simpan profil');
        $this->get(route('account.feedback.index'))->assertOk()->assertSee('Kritik & saran');
    }

    public function test_customer_can_update_profile_name_email_and_photo(): void
    {
        $publicDisk = Storage::fake('public');

        $this->actingAs($this->customer, 'customer')
            ->patch(route('account.profile.update'), [
                'name' => 'Nama Pelanggan Baru',
                'email' => 'nama-baru@example.test',
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'avatar.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR4nGNgAAIAAAUAAXpeqz8AAAAASUVORK5CYII='),
                ),
            ])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->customer->refresh();
        $this->assertSame('Nama Pelanggan Baru', $this->customer->name);
        $this->assertSame('nama-baru@example.test', $this->customer->email);
        $this->assertNotNull($this->customer->profile_photo_path);
        $publicDisk->assertExists($this->customer->profile_photo_path);

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Nama Pelanggan Baru')
            ->assertSee($publicDisk->url($this->customer->profile_photo_path), false)
            ->assertSee('data-current-user-photo-url="'.e($publicDisk->url($this->customer->profile_photo_path)).'"', false);

        $previousPhotoPath = $this->customer->profile_photo_path;
        $this->patch(route('account.profile.update'), [
            'name' => 'Nama Pelanggan Baru',
            'email' => 'nama-baru@example.test',
            'remove_profile_photo' => '1',
        ])->assertRedirect(route('account.profile.edit'));

        $this->customer->refresh();
        $this->assertNull($this->customer->profile_photo_path);
        $publicDisk->assertMissing($previousPhotoPath);
    }

    public function test_customer_profile_rejects_non_image_uploads_and_duplicate_email(): void
    {
        Storage::fake('public');

        $this->actingAs($this->customer, 'customer')
            ->from(route('account.profile.edit'))
            ->patch(route('account.profile.update'), [
                'name' => 'Nama Tetap',
                'email' => 'nama-baru@example.test',
                'profile_photo' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('profile_photo');

        $otherUser = User::factory()->create(['email' => 'used@example.test']);
        $this->patch(route('account.profile.update'), [
            'name' => 'Nama Tetap',
            'email' => $otherUser->email,
        ])->assertSessionHasErrors('email');
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

    public function test_cafe_menu_without_description_uses_a_compact_name_and_price_layout(): void
    {
        $this->menuItem->update([
            'description' => '   ',
            'image_path' => null,
            'category' => 'Minuman',
        ]);

        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertSee('Minuman')
            ->assertSee('Rp25.000')
            ->assertSee('cafe-menu-item--compact', false)
            ->assertDontSee('cafe-menu-item-description', false);
    }

    public function test_cafe_menu_without_image_renders_compact_without_placeholder_or_description(): void
    {
        $this->menuItem->update([
            'description' => 'Minuman uji',
            'image_path' => null,
        ]);

        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertDontSee('Minuman uji')
            ->assertSee('cafe-menu-item--compact', false)
            ->assertSee('cafe-menu-item--no-image', false)
            ->assertDontSee('cafe-menu-item-placeholder', false)
            ->assertDontSee('cafe-menu-item-image', false);
    }

    public function test_public_cafe_catalog_does_not_disclose_available_stock(): void
    {
        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertSee('id="app-toast"', false)
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

    public function test_customer_can_add_another_same_shop_product_without_leaving_cart(): void
    {
        $secondItem = MenuItem::create([
            'cafe_id' => $this->cafe->id,
            'name' => 'Latte UAT',
            'price' => '30000.00',
            'stock' => 4,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
        $cart = [
            'seller_type' => 'cafe',
            'seller_id' => $this->cafe->id,
            'cafe_id' => $this->cafe->id,
            'items' => [$this->menuItem->id => 1],
        ];

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => $cart])
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Tambah produk lain')
            ->assertSee('Latte UAT')
            ->assertSee('data-cart-add-form')
            ->assertSee('data-customer-authenticated="true"', false);

        $this->postJson(route('cart.items.add', [$this->cafe->slug, $secondItem->id]))
            ->assertOk()
            ->assertJsonPath('cart_count', 2)
            ->assertJsonPath('total', 55000)
            ->assertJsonPath('items.1.name', 'Latte UAT');

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertSee('Latte UAT')
            ->assertSee('Rp55.000');
    }

    public function test_menu_addons_are_selected_in_catalog_and_reserved_separately_at_checkout(): void
    {
        $addon = MenuItem::create([
            'cafe_id' => $this->cafe->id,
            'name' => 'Add Cheese',
            'category' => 'Topping',
            'price' => '5000.00',
            'stock' => 8,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
        $this->menuItem->addons()->attach($addon->id);

        $this->get(route('cafes.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Kopi Susu UAT')
            ->assertSee('Pilih menu')
            ->assertSee('Cheese')
            ->assertDontSee('Add Cheese');

        $this->actingAs($this->customer, 'customer')
            ->postJson(route('cart.items.add', [$this->cafe->slug, $this->menuItem->id]), [
                'addon_quantities' => [$addon->id => 2],
            ])
            ->assertOk()
            ->assertJsonPath('cart_count', 3)
            ->assertJsonPath('total', 35000);

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('+ Cheese')
            ->assertSee('Rp35.000');
        $this->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('+ Cheese')
            ->assertSee('Rp35.000');

        $this->post(route('checkout.store', $this->cafe->slug), [
            'customer_name' => 'Pembeli Topping',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
            'sender_bank' => 'bca',
        ])->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $parentOrderItem = $order->items->firstWhere('parent_order_item_id', null);
        $addonOrderItem = $order->items->firstWhere('parent_order_item_id', $parentOrderItem->id);

        $this->assertSame('35000.00', $order->total);
        $this->assertNotNull($addonOrderItem);
        $this->assertSame('Cheese', $addonOrderItem->name_snapshot);
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(2, $addon->fresh()->reserved_stock);
        $this->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('+ Cheese × 2');

        app(OrderService::class)->cancelByCustomer($order, $this->customer);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(0, $addon->fresh()->reserved_stock);
    }

    public function test_repeated_checkout_with_same_key_reuses_order_and_reserves_stock_once(): void
    {
        $key = (string) Str::uuid();
        $service = app(OrderService::class);
        $first = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest UAT', '081234567890', null, null, $key);
        $retry = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest UAT', '081234567890', null, null, $key);
        $next = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest UAT 2', '081234567891', null);

        $this->assertSame($first->id, $retry->id);
        $this->assertSame($first->display_code, $retry->display_code);
        $this->assertSame('WN-0001', $first->display_code);
        $this->assertSame('WN-0002', $next->display_code);
        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(2, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(5, $this->menuItem->fresh()->stock);
    }

    public function test_rejected_transfer_can_be_resubmitted_with_a_new_payment_window(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Pembeli Transfer Ulang',
            null,
            $this->customer->id,
            null,
            (string) Str::uuid(),
            'transfer',
            'mandiri',
        );
        Storage::fake('local');
        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.payment.submit', $order->public_code), [
                'proof' => UploadedFile::fake()->create('first-proof.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('orders.show', $order->public_code));

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.reject-payment', $order->public_code), [
                'reason' => 'Bukti kurang jelas.',
            ])
            ->assertRedirect(route('owner.orders.show', $order->public_code));

        $order->refresh();
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame('rejected', $order->payment_status);
        $this->assertTrue($order->payment_expires_at->isFuture());
        $this->assertSame(1, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame('rejected', $order->payments()->firstOrFail()->status);

        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.payment.submit', $order->public_code), [
                'proof' => UploadedFile::fake()->create('second-proof.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('orders.show', $order->public_code));
        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.confirm-payment', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(4, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->assertSame(1, $order->payments()->where('status', 'confirmed')->count());
    }

    public function test_empty_cart_and_overstock_checkout_do_not_create_orders_or_change_stock(): void
    {
        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => []]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890', 'payment_method' => 'transfer', 'sender_bank' => 'bca',
            ])
            ->assertSessionHasErrors('cart');

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 6]]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890', 'payment_method' => 'transfer', 'sender_bank' => 'bca',
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

        $cart = ['cafe_id' => $this->cafe->id, 'items' => [
                $this->menuItem->id => 1,
                $secondItem->id => 2,
            ]];
        $this->actingAs($buyer, 'customer')
            ->withSession(['order_cart' => $cart])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('name="customer_whatsapp" type="tel" required', false);

        $this->withSession(['order_cart' => $cart])
            ->post(route('checkout.store', $this->cafe->slug), ['customer_name' => 'Akun UAT', 'payment_method' => 'transfer', 'sender_bank' => 'bca'])
            ->assertSessionHasErrors('customer_whatsapp');

        $this->withSession(['order_cart' => $cart])
            ->post(route('checkout.store', $this->cafe->slug), ['customer_name' => 'Akun UAT', 'customer_whatsapp' => '081234567890', 'payment_method' => 'transfer', 'sender_bank' => 'bca'])
            ->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame($buyer->id, $order->user_id);
        $this->assertSame('50000.00', $order->total);
        $this->get(route('orders.history'))
            ->assertOk()
            ->assertSee($order->display_code)
            ->assertSee('Kopi Susu UAT × 1')
            ->assertSee('Roti UAT × 2')
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_unpaid_order_cannot_be_marked_ready_and_does_not_decrement_stock(): void
    {
        $order = app(OrderService::class)->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Guest UAT',
            '628123456789',
            null,
        );

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.status', $order->public_code), ['status' => 'ready', 'ready_confirmed' => '1'])
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
            ->assertSee($order->display_code)
            ->assertSee('Menunggu Pembayaran');

        $this->get(route('orders.status', $order->public_code))
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_AWAITING_PAYMENT);
    }

    public function test_pickup_code_records_handoff_and_customer_confirms_receipt(): void
    {
        $service = app(OrderService::class);
        $this->cafe->update(['whatsapp_number' => '08123456789']);
        $order = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest Pickup', null, $this->customer->id);
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/test.pdf', 'test-proof');
        $service->submitPayment($order, 'payment-proofs/test.pdf');
        $order = $service->confirmPayment($order->fresh(), $this->owner);

        $this->actingAs($this->owner)
            ->post(route('owner.orders.pickup'), ['code' => $order->display_code])
            ->assertSessionHasErrors('code');
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);

        $printJob = ReceiptPrintJob::where('order_id', $order->id)->where('kind', 'automatic')->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->customer->id,
            'type' => OrderPaymentVerifiedNotification::class,
        ]);
        $paymentNotice = $this->customer->notifications()
            ->where('type', OrderPaymentVerifiedNotification::class)
            ->firstOrFail();
        $this->assertStringContainsString('masuk daftar kedai', $paymentNotice->data['message']);
        $this->assertFalse(OrderResource::getEloquentQuery()->whereKey($order->id)->exists());
        $this->assertTrue(OrderReportResource::getEloquentQuery()->whereKey($order->id)->exists());
        $this->assertSame('Atur Pesanan', OrderResource::getNavigationGroup());
        $this->get(OrderReportResource::getUrl())
            ->assertOk()
            ->assertSee($order->display_code)
            ->assertDontSee('Tandai siap diambil');
        $this->actingAs($this->owner, 'web')
            ->get(route('owner.orders.index'))
            ->assertOk()
            ->assertSee('Daftar Pesanan')
            ->assertSee('Laporan Pesanan')
            ->assertSee('Belum ada pesanan.')
            ->assertDontSee($order->display_code);
        $this->get(route('owner.orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Lihat / cetak nota')
            ->assertDontSee('Mulai siapkan');
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'ready', 'ready_confirmed' => '1'])
            ->assertSessionHasErrors('status');
        $this->post(route('owner.print-jobs.status', $printJob), [
            'state' => 'failed', 'last_error' => 'Printer offline',
        ])->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertSame('failed', $printJob->fresh()->state);
        $this->assertSame($this->owner->id, $printJob->fresh()->reported_by);
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'ready', 'ready_confirmed' => '1'])
            ->assertSessionHasErrors('status');
        $this->post(route('owner.print-jobs.status', $printJob), ['state' => 'printed'])
            ->assertRedirect(route('owner.orders.print', $order->public_code));
        $this->assertNotNull($printJob->fresh()->printed_at);
        $this->assertNotNull($printJob->fresh()->reported_at);
        $this->get(OrderReportResource::getUrl())
            ->assertOk()
            ->assertSee('Tandai siap diambil');
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

        $this->get(route('owner.orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('Laporan Pesanan')
            ->assertDontSee('ready_confirmed', false)
            ->assertDontSee('Mulai siapkan');
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'ready'])
            ->assertSessionHasErrors('ready_confirmed');
        $this->post(route('owner.orders.status', $order->public_code), ['status' => 'ready', 'ready_confirmed' => '1'])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_READY, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->ready_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->customer->id,
            'type' => OrderReadyNotification::class,
        ]);
        $pickupNotices = $this->customer->notifications()
            ->where('type', OrderReadyNotification::class)
            ->get()
            ->pluck('data.message');
        $this->assertTrue($pickupNotices->contains(
            fn (string $message): bool => str_contains($message, 'sudah siap diambil'),
        ));

        $this->post(route('owner.orders.pickup'), ['code' => $order->display_code])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, $order->fresh()->status);
        $this->assertNull($order->fresh()->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => Order::STATUS_READY,
            'to_status' => Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
        ]);

        $this->actingAs($this->customer, 'customer')
            ->get(route('orders.show', $order->public_code))
            ->assertOk()
            ->assertSee('receipt-confirmation-dialog', false)
            ->assertSee('Pesanan sudah diterima?')
            ->assertSee('Tidak, ada kendala')
            ->assertSee('https://wa.me/628123456789', false);

        $otherCustomer = User::factory()->create(['role' => 'user']);
        $this->actingAs($otherCustomer, 'customer')
            ->post(route('orders.confirm-received', $order->public_code))
            ->assertNotFound();

        $this->actingAs($this->customer, 'customer')
            ->post(route('orders.confirm-received', $order->public_code))
            ->assertRedirect(route('orders.show', $order->public_code));
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.pickup'), ['code' => $order->display_code])
            ->assertSessionHasErrors('code');
    }

    public function test_pickup_and_delivery_orders_complete_automatically_after_24_hours(): void
    {
        $service = app(OrderService::class);
        $this->cafe->update(['whatsapp_number' => '08123456789']);
        $order = $service->placeOrder($this->cafe, [$this->menuItem->id => 1], 'Guest Pickup', null, $this->customer->id);
        $order->update([
            'status' => Order::STATUS_READY,
            'payment_status' => 'paid',
            'ready_at' => now(),
            'receipt_confirmation_deadline_started_at' => now(),
        ]);
        $this->assertNotNull($order->fresh()->receipt_confirmation_deadline_started_at);
        $this->cafe->update(['delivery_base_fee' => 1000, 'delivery_fee_per_km' => 500]);
        $deliveryOrder = $service->placeOrder(
            $this->cafe,
            [$this->menuItem->id => 1],
            'Guest Delivery',
            null,
            $this->customer->id,
            null,
            null,
            'transfer',
            null,
            [],
            'delivery',
            'Jl. Kopi Nomor 10, Samarinda',
        );
        $deliveryOrder->update(['status' => Order::STATUS_PAID, 'payment_status' => 'paid']);
        ReceiptPrintJob::create([
            'order_id' => $deliveryOrder->id,
            'kind' => 'automatic',
            'state' => 'printed',
            'dedupe_key' => 'uat-delivery-'.$deliveryOrder->id,
            'payload' => ['public_code' => $deliveryOrder->public_code],
            'attempts' => 1,
            'printed_at' => now(),
        ]);
        $service->transition($deliveryOrder, $this->owner, Order::STATUS_DELIVERING);
        $service->transition($deliveryOrder, $this->owner, Order::STATUS_AWAITING_RECEIPT_CONFIRMATION);
        $this->assertNotNull($deliveryOrder->fresh()->delivery_started_at);
        $this->assertNotNull($deliveryOrder->fresh()->receipt_confirmation_deadline_started_at);
        $deliveryNotices = $this->customer->notifications()
            ->where('type', OrderReadyNotification::class)
            ->get()
            ->pluck('data.message');
        $this->assertTrue($deliveryNotices->contains(
            fn (string $message): bool => str_contains($message, 'sedang dikirim oleh kurir'),
        ));
        $this->assertSame(2, $this->customer->notifications()->where('type', OrderReadyNotification::class)->count());
        $this->actingAs($this->customer, 'customer')
            ->get(route('orders.show', $deliveryOrder->public_code))
            ->assertOk()
            ->assertSee('receipt-confirmation-dialog', false)
            ->assertSee('hubungi admin kedai');

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.pickup'), ['code' => $order->display_code])
            ->assertSessionHas('status');
        $this->assertSame(Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, $order->fresh()->status);

        $this->travel(23)->hours();
        $this->assertSame(0, $service->completeUnconfirmedOrders());
        $this->assertSame(Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, $order->fresh()->status);

        $this->travel(1)->hours();
        $this->assertSame(2, $service->completeUnconfirmedOrders());
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame(Order::STATUS_COMPLETED, $deliveryOrder->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
            'to_status' => Order::STATUS_COMPLETED,
        ]);
        $this->assertSame(0, $service->completeUnconfirmedOrders(), 'Automatic completion must be idempotent.');
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
            ->assertSee('Beans Roastery UAT')
            ->assertSee('Rp45.000')
            ->assertSee('roastery-product-card--compact', false)
            ->assertDontSee('Biji Kopi');

        $this->actingAs($this->customer, 'customer')
            ->post(route('roastery.cart.items.add', [$roastery->slug, $product->id]))
            ->assertRedirect(route('cart.show'));
        $this->actingAs($this->customer, 'customer')->get(route('roastery.checkout.show', $roastery->slug))->assertOk();
        $this->actingAs($this->customer, 'customer')->post(route('roastery.checkout.store', $roastery->slug), [
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
            ->assertSee($order->display_code);
        $this->get(route('orders.receipt', $order->public_code))
            ->assertOk()
            ->assertSee('Cetak / Simpan PDF')
            ->assertSee('Tunjukkan kode pesanan ini kepada staf')
            ->assertDontSee('<img');

        $this->actingAs($this->owner, 'web')
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

    public function test_cart_rejects_products_from_a_second_shop_and_explains_single_shop_orders(): void
    {
        $roastery = Roastery::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'published',
        ]);
        $roasteryProduct = MenuItem::create([
            'roastery_id' => $roastery->id,
            'name' => 'Beans Kedai Kedua',
            'category' => 'Beans',
            'price' => '85000.00',
            'stock' => 5,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->customer, 'customer')
            ->withSession([
                'order_cart' => [
                    'seller_type' => 'cafe',
                    'seller_id' => $this->cafe->id,
                    'cafe_id' => $this->cafe->id,
                    'items' => [$this->menuItem->id => 1],
                ],
            ])
            ->post(route('roastery.cart.items.add', [$roastery->slug, $roasteryProduct->id]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'cart' => 'Satu pesanan hanya boleh berisi produk dari satu kedai. Selesaikan pesanan sebelumnya atau kosongkan keranjang untuk memesan dari kedai ini.',
            ]);

        $this->assertSame(
            [$this->menuItem->id => 1],
            session('order_cart.items'),
            'A failed cross-shop add must preserve the existing cart.',
        );
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

    public function test_customer_can_send_one_feedback_for_their_completed_order(): void
    {
        $order = Order::create([
            'cafe_id' => $this->cafe->id,
            'user_id' => $this->customer->id,
            'public_code' => 'FDBK1234',
            'display_code' => 'WN-9001',
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'customer_name' => 'Pembeli UAT',
            'customer_whatsapp' => '081234567890',
            'subtotal' => '25000.00',
            'total' => '25000.00',
            'completed_at' => now(),
        ]);

        $this->actingAs($this->customer, 'customer')
            ->get(route('orders.feedback.create', $order->public_code))
            ->assertOk()
            ->assertSee($this->cafe->name)
            ->assertSee('Pesanan selesai');
        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Kritik &amp; saran', false)
            ->assertSee(route('account.feedback.index'), false);
        $this->get(route('account.feedback.index'))
            ->assertOk()
            ->assertSee('Beri masukan');

        $this->post(route('orders.feedback.store', $order->public_code), [
            'category' => 'suggestion',
            'customer_name' => 'Pembeli UAT',
            'customer_contact' => '081234567890',
            'message' => 'Mohon sediakan pilihan susu oat.',
        ])->assertRedirect(route('account'));

        $this->assertDatabaseHas('customer_feedback', [
            'order_id' => $order->id,
            'cafe_id' => $this->cafe->id,
            'category' => 'suggestion',
            'customer_contact' => '081234567890',
        ]);

        $this->get(route('account'))
            ->assertSee('Masukan terkirim')
            ->assertDontSee('Beri masukan');
        $this->post(route('orders.feedback.store', $order->public_code), [
            'category' => 'criticism',
            'customer_name' => 'Pembeli UAT',
            'customer_contact' => '081234567890',
            'message' => 'Masukan kedua untuk pesanan yang sama.',
        ])->assertRedirect(route('account'));
        $this->assertDatabaseCount('customer_feedback', 1);
    }

    public function test_feedback_for_order_requires_owner_and_completed_status(): void
    {
        $order = Order::create([
            'cafe_id' => $this->cafe->id,
            'user_id' => $this->customer->id,
            'public_code' => 'FDBK5678',
            'display_code' => 'WN-9002',
            'status' => Order::STATUS_READY,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'customer_name' => 'Pembeli UAT',
            'subtotal' => '25000.00',
            'total' => '25000.00',
        ]);

        $this->actingAs($this->customer, 'customer')
            ->get(route('orders.feedback.create', $order->public_code))
            ->assertNotFound();

        $order->update(['status' => Order::STATUS_COMPLETED, 'completed_at' => now()]);
        $otherCustomer = User::factory()->create(['role' => 'user']);

        $this->actingAs($otherCustomer, 'customer')
            ->post(route('orders.feedback.store', $order->public_code), [
                'category' => 'suggestion',
                'customer_name' => 'Pelanggan Lain',
                'customer_contact' => '081234567891',
                'message' => 'Saran dari akun yang bukan pemilik pesanan.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('customer_feedback', 0);
    }

    public function test_qris_and_transfer_remain_manual_and_do_not_call_bank_services(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cafes/payment-qr/static-cafe.png', 'fake-qris-image');
        $this->cafe->update([
            'payment_qr_path' => 'cafes/payment-qr/static-cafe.png',
            'payment_instructions' => 'Gunakan QRIS statis kedai dan tunggu verifikasi staf.',
            'bank_name' => 'BCA',
            'bank_account_number' => '0123456789',
            'bank_account_name' => 'Wadah Ngopi',
        ]);
        $cart = ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]];

        $this->actingAs($this->customer, 'customer')->withSession(['order_cart' => $cart])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('data-payment-selector', false)
            ->assertSee('data-payment-panel="qris"', false)
            ->assertSee('data-payment-panel="transfer"', false)
            ->assertSee('Transfer')
            ->assertSee('Instruksi transfer')
            ->assertSee('Nomor rekening mitra tampil setelah pesanan dibuat')
            ->assertDontSee('Transfer dari bank apa?')
            ->assertDontSee('0123456789')
            ->assertSee('Kode QRIS akan ditampilkan di halaman menunggu pembayaran')
            ->assertDontSee('static-cafe.png')
            ->assertSee('Ringkasan pesanan')
            ->assertSee('Biaya pengiriman')
            ->assertSee('Tunai di kedai')
            ->assertDontSee('storage/cafes/payment-qr/static-cafe.png', false);

        $this->actingAs($this->customer, 'customer')->post(route('checkout.store', $this->cafe->slug), [
            'customer_name' => 'Pembeli Transfer',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
        ])->assertRedirect();
        $transferOrder = Order::where('payment_method', 'transfer')->firstOrFail();
        $this->get(route('orders.show', $transferOrder->public_code))
            ->assertOk()
            ->assertSee('Pembayaran transfer')
            ->assertSee('Transfer ke rekening mitra')
            ->assertDontSee('Bank pengirim pilihan:')
            ->assertSee('BCA')
            ->assertSee('0123456789')
            ->assertSee('Atas nama')
            ->assertSee('Bukti pembayaran seperti apa yang perlu diunggah?')
            ->assertSee('Gunakan QRIS statis kedai');

        Storage::fake('local');
        $this->post(route('orders.payment.submit', $transferOrder->public_code), [
            'proof' => UploadedFile::fake()->create('transfer.png', 10, 'image/png'),
        ])->assertRedirect(route('orders.show', $transferOrder->public_code));
        $this->assertSame(Order::STATUS_PENDING_VERIFICATION, $transferOrder->fresh()->status);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $transferOrder->id,
            'payment_method' => 'transfer',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->customer, 'customer')->withSession(['order_cart' => $cart])
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

    public function test_missing_qris_file_disables_qris_and_shows_shop_contact_option(): void
    {
        Storage::fake('public');
        $this->cafe->update([
            'payment_qr_path' => 'cafes/payment-qr/missing.png',
            'whatsapp_number' => '081234567890',
        ]);
        $cart = ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 1]];

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => $cart])
            ->get(route('checkout.show', $this->cafe->slug))
            ->assertOk()
            ->assertSee('QRIS kedai saat ini belum tersedia')
            ->assertSee('https://wa.me/6281234567890')
            ->assertDontSee('value="qris"');

        $this->withSession(['order_cart' => $cart])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli QRIS',
                'customer_whatsapp' => '081234567890',
                'payment_method' => 'qris',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->withSession(['order_cart' => $cart])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli Tanpa Metode',
                'customer_whatsapp' => '081234567890',
            ])
            ->assertSessionHasErrors('payment_method');
    }
}
