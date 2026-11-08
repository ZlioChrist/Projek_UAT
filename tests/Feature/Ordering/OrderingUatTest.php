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

        $this->actingAs($this->owner, 'web')
            ->post(route('owner.orders.confirm-payment', $order->public_code))
            ->assertRedirect(route('owner.orders.print', $order->public_code));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(3, $this->menuItem->fresh()->stock);
        $this->assertSame(0, $this->menuItem->fresh()->reserved_stock);
        $this->get(route('orders.receipt', $order->public_code))
            ->assertOk()
            ->assertSee('Lunas / terverifikasi')
            ->assertSee('Tunjukkan bukti pembayaran dari aplikasi bank/e-wallet')
            ->assertDontSee('QRIS')
            ->assertDontSee('<img');
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
            ->assertSee('data-customer-authenticated="false"', false)
            ->assertSee('Masuk sebagai customer');
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
            ->assertSee('Lacak pesanan')
            ->assertSee('Tempat tersimpan')
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
        Storage::fake('public');

        $this->actingAs($this->customer, 'customer')
            ->patch(route('account.profile.update'), [
                'name' => 'Nama Pelanggan Baru',
                'email' => 'nama-baru@example.test',
                'profile_photo' => UploadedFile::fake()->image('avatar.png', 120, 120),
            ])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->customer->refresh();
        $this->assertSame('Nama Pelanggan Baru', $this->customer->name);
        $this->assertSame('nama-baru@example.test', $this->customer->email);
        $this->assertNotNull($this->customer->profile_photo_path);
        Storage::disk('public')->assertExists($this->customer->profile_photo_path);

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Nama Pelanggan Baru')
            ->assertSee(Storage::disk('public')->url($this->customer->profile_photo_path), false)
            ->assertSee('data-current-user-photo-url="'.e(Storage::disk('public')->url($this->customer->profile_photo_path)).'"', false);

        $previousPhotoPath = $this->customer->profile_photo_path;
        $this->patch(route('account.profile.update'), [
            'name' => 'Nama Pelanggan Baru',
            'email' => 'nama-baru@example.test',
            'remove_profile_photo' => '1',
        ])->assertRedirect(route('account.profile.edit'));

        $this->customer->refresh();
        $this->assertNull($this->customer->profile_photo_path);
        Storage::disk('public')->assertMissing($previousPhotoPath);
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
        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => []]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890', 'payment_method' => 'transfer',
            ])
            ->assertSessionHasErrors('cart');

        $this->actingAs($this->customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [$this->menuItem->id => 6]]])
            ->post(route('checkout.store', $this->cafe->slug), [
                'customer_name' => 'Pembeli UAT', 'customer_whatsapp' => '081234567890', 'payment_method' => 'transfer',
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

        $this->actingAs($buyer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $this->cafe->id, 'items' => [
                $this->menuItem->id => 1,
                $secondItem->id => 2,
            ]]])
            ->post(route('checkout.store', $this->cafe->slug), ['customer_name' => 'Akun UAT', 'payment_method' => 'transfer'])
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

        $this->actingAs($this->owner, 'web')
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
            ->assertSee($order->public_code);
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
            ->assertSee('BCA')
            ->assertSee('0123456789')
            ->assertSee('Wadah Ngopi')
            ->assertSee('static-cafe.png')
            ->assertSee('storage/cafes/payment-qr/static-cafe.png', false)
            ->assertSee('Ringkasan pesanan')
            ->assertSee('Biaya pengiriman')
            ->assertSee('Tunai di kedai')
            ->assertSee('QRIS statis perlu dikonfirmasi manual oleh kedai.');

        $this->actingAs($this->customer, 'customer')->post(route('checkout.store', $this->cafe->slug), [
            'customer_name' => 'Pembeli Transfer',
            'customer_whatsapp' => '081234567890',
            'payment_method' => 'transfer',
        ])->assertRedirect();
        $transferOrder = Order::where('payment_method', 'transfer')->firstOrFail();
        $this->get(route('orders.show', $transferOrder->public_code))
            ->assertOk()
            ->assertSee('Pembayaran transfer')
            ->assertSee('Detail transfer')
            ->assertSee('0123456789')
            ->assertSee('Atas nama')
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
