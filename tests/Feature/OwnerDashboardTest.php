<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\FacilityResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Widgets\BestSellingProductsChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\OrderVolumeChart;
use App\Filament\Widgets\RevenueTrendChart;
use App\Models\Cafe;
use App\Models\Facility;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Roastery;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_table_formats_total_in_rupiah_without_intl(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create([
            'owner_id' => $owner->id,
            'accepts_cash' => true,
        ]);
        $menuItem = MenuItem::create([
            'cafe_id' => $cafe->id,
            'name' => 'Kopi Uji',
            'category' => 'Minuman',
            'price' => '25000.00',
            'stock' => 5,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        app(OrderService::class)->placeOrder(
            $cafe,
            [$menuItem->id => 1],
            'Pelanggan Uji',
            '081234567890',
            null,
            paymentMethod: 'cash',
        );

        $this->actingAs($owner, 'web')
            ->get(OrderResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Rp 25.000');
    }

    public function test_cafe_owner_sees_only_their_menu_stock_and_facilities(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create([
            'name' => 'Kafe Milik Saya',
            'owner_id' => $owner->id,
            'whatsapp_number' => '08123456789',
        ]);
        $otherCafe = Cafe::factory()->create(['name' => 'Kafe Orang Lain']);

        MenuItem::create([
            'cafe_id' => $cafe->id,
            'name' => 'Kopi Dashboard',
            'category' => 'Kopi',
            'price' => '18000.00',
            'stock' => 7,
            'reserved_stock' => 2,
            'is_active' => true,
        ]);
        MenuItem::create([
            'cafe_id' => $otherCafe->id,
            'name' => 'Menu Rahasia Kedai Lain',
            'price' => '20000.00',
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
        Facility::create(['cafe_id' => $cafe->id, 'name' => 'WiFi Gratis']);

        $this->actingAs($owner, 'web')
            ->get(route('owner.dashboard'))
            ->assertRedirect(Dashboard::getUrl());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee('Kelola Kafe')
            ->assertSee('Kafe Saya')
            ->assertSee('Menu & Produk')
            ->assertSee('Pesanan Berjalan')
            ->assertSee(CafeResource::getUrl('index'))
            ->assertSee(MenuItemResource::getUrl('index'))
            ->assertSee(OrderResource::getUrl('index'))
            ->assertDontSee('Lihat Website')
            ->assertDontSee('Kopi Dashboard')
            ->assertDontSee('WiFi Gratis')
            ->assertDontSee('Menu Rahasia Kedai Lain');

        $this->get(MenuItemResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Kopi Dashboard')
            ->assertDontSee('Menu Rahasia Kedai Lain');
        $this->get(FacilityResource::getUrl('index'))
            ->assertOk()
            ->assertSee('WiFi Gratis');
        $this->get(OrderResource::getUrl('index'))->assertOk();
    }

    public function test_roastery_owner_sees_only_their_roastery_menu_and_stock(): void
    {
        $owner = User::factory()->create(['role' => 'roastery']);
        $roastery = Roastery::factory()->create([
            'name' => 'Roastery Milik Saya',
            'owner_id' => $owner->id,
            'whatsapp_number' => '081298765432',
        ]);
        $otherRoastery = Roastery::factory()->create(['name' => 'Roastery Orang Lain']);

        MenuItem::create([
            'roastery_id' => $roastery->id,
            'name' => 'Biji Kopi Dashboard',
            'category' => 'Beans',
            'price' => '85000.00',
            'stock' => 10,
            'reserved_stock' => 4,
            'is_active' => true,
        ]);
        MenuItem::create([
            'roastery_id' => $otherRoastery->id,
            'name' => 'Biji Kopi Kedai Lain',
            'price' => '90000.00',
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($owner, 'web')
            ->get(route('owner.dashboard'))
            ->assertRedirect(Dashboard::getUrl());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee('Kelola Roastery')
            ->assertSee('Roastery Saya')
            ->assertSee('Menu & Produk')
            ->assertSee('Pesanan Berjalan')
            ->assertDontSee('Lihat Website')
            ->assertDontSee('Biji Kopi Dashboard')
            ->assertDontSee('Biji Kopi Kedai Lain');

        $this->get(MenuItemResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Biji Kopi Dashboard')
            ->assertDontSee('Biji Kopi Kedai Lain');
        $this->get(OrderResource::getUrl('index'))->assertOk();
    }

    public function test_dashboard_charts_show_only_the_owners_non_cancelled_orders_and_paid_revenue(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $otherCafe = Cafe::factory()->create();
        $orderDate = now()->subDays(2);

        $this->createDashboardOrder($cafe, 'CHART001', $orderDate, Order::STATUS_PAID, 'paid', 25000);
        $this->createDashboardOrder($cafe, 'CHART002', $orderDate, Order::STATUS_CANCELLED, 'cancelled', 15000);
        $this->createDashboardOrder($otherCafe, 'CHART003', $orderDate, Order::STATUS_PAID, 'paid', 90000);

        $this->actingAs($owner, 'web')
            ->get(Dashboard::getUrl())
            ->assertOk();

        $this->assertContains(OrderVolumeChart::class, app(Dashboard::class)->getWidgets());
        $this->assertContains(RevenueTrendChart::class, app(Dashboard::class)->getWidgets());
        $orderChart = $this->chartData(OrderVolumeChart::class);
        $revenueChart = $this->chartData(RevenueTrendChart::class);
        $dayIndex = array_search($orderDate->format('d/m'), $orderChart['labels'], true);

        $this->assertNotFalse($dayIndex);
        $this->assertSame(2, $orderChart['datasets'][0]['data'][$dayIndex]);
        $this->assertSame(25000.0, $revenueChart['datasets'][0]['data'][$dayIndex]);
    }

    public function test_dashboard_status_and_best_selling_charts_are_scoped_to_the_owner(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $otherCafe = Cafe::factory()->create();
        $createdAt = now()->subDays(2);

        $newOrder = $this->createDashboardOrder($cafe, 'CHART101', $createdAt, Order::STATUS_AWAITING_PAYMENT, 'unpaid', 10000);
        $processingOrder = $this->createDashboardOrder($cafe, 'CHART102', $createdAt, Order::STATUS_PREPARING, 'paid', 20000);
        $readyOrder = $this->createDashboardOrder($cafe, 'CHART103', $createdAt, Order::STATUS_READY, 'paid', 30000);
        $completedOrder = $this->createDashboardOrder($cafe, 'CHART104', $createdAt, Order::STATUS_COMPLETED, 'paid', 40000);
        $cancelledOrder = $this->createDashboardOrder($cafe, 'CHART105', $createdAt, Order::STATUS_CANCELLED, 'cancelled', 50000);
        $otherOrder = $this->createDashboardOrder($otherCafe, 'CHART106', $createdAt, Order::STATUS_PREPARING, 'paid', 60000);

        $kopi = $this->createDashboardOrderItem($processingOrder, 'Kopi Susu', 3);
        $this->createDashboardOrderItem($completedOrder, 'Kopi Susu', 1);
        $this->createDashboardOrderItem($readyOrder, 'Latte', 2);
        $this->createDashboardOrderItem($cancelledOrder, 'Produk Batal', 20);
        $this->createDashboardOrderItem($otherOrder, 'Produk Kedai Lain', 30);
        $this->createDashboardOrderItem($processingOrder, 'Tambahan Gula', 4, $kopi->id);
        $this->createDashboardOrderItem($newOrder, 'Pesanan Baru', 1);

        $this->actingAs($owner, 'web')->get(Dashboard::getUrl())->assertOk();
        $widgets = app(Dashboard::class)->getWidgets();
        $this->assertContains(OrderStatusChart::class, $widgets);
        $this->assertContains(BestSellingProductsChart::class, $widgets);

        $statusChart = $this->chartData(OrderStatusChart::class);
        $this->assertSame(['Baru masuk', 'Sedang diproses', 'Siap diambil', 'Selesai', 'Dibatalkan'], $statusChart['labels']);
        $this->assertSame([1, 1, 1, 1, 1], $statusChart['datasets'][0]['data']);

        $productChart = $this->chartData(BestSellingProductsChart::class);
        $this->assertSame(['Kopi Susu', 'Latte', 'Pesanan Baru'], $productChart['labels']);
        $this->assertSame([4, 2, 1], $productChart['datasets'][0]['data']);
    }

    private function createDashboardOrder(
        Cafe $cafe,
        string $publicCode,
        \Illuminate\Support\Carbon $createdAt,
        string $status,
        string $paymentStatus,
        int $total,
    ): Order {
        $order = new Order;
        $order->forceFill([
            'cafe_id' => $cafe->id,
            'public_code' => $publicCode,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'customer_name' => 'Pelanggan Uji',
            'subtotal' => $total,
            'total' => $total,
            'paid_at' => $paymentStatus === 'paid' ? $createdAt : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $order;
    }

    private function createDashboardOrderItem(
        Order $order,
        string $name,
        int $quantity,
        ?int $parentOrderItemId = null,
    ): OrderItem {
        return OrderItem::create([
            'order_id' => $order->id,
            'parent_order_item_id' => $parentOrderItemId,
            'name_snapshot' => $name,
            'unit_price' => 10000,
            'quantity' => $quantity,
            'line_total' => 10000 * $quantity,
        ]);
    }

    /**
     * @param  class-string  $chartClass
     * @return array<string, mixed>
     */
    private function chartData(string $chartClass): array
    {
        $chart = app($chartClass);
        $method = new \ReflectionMethod($chartClass, 'getCachedData');

        return $method->invoke($chart);
    }
}
