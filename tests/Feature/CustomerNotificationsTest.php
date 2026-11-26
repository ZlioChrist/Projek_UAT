<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Order;
use App\Models\User;
use App\Notifications\BusinessUpdateNotification;
use App\Notifications\OrderReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_and_open_their_notifications(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $customer->notify(new BusinessUpdateNotification(
            'Menu baru',
            'Kafe favoritmu menambahkan menu.',
            'Kafe Contoh',
            url('/explore'),
        ));

        $this->actingAs($customer, 'customer')
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Menu baru')
            ->assertSee('Kafe favoritmu menambahkan menu.');

        $notification = $customer->notifications()->firstOrFail();
        $this->post(route('notifications.open', $notification->id))
            ->assertRedirect(url('/explore'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_customer_can_check_their_unread_notification_count(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $otherCustomer = User::factory()->create(['role' => 'user']);

        $customer->notify(new BusinessUpdateNotification(
            'Pembaruan dibaca',
            'Notifikasi ini sudah dibaca.',
            'Kafe Contoh',
            url('/explore'),
        ));
        $customer->notifications()->firstOrFail()->markAsRead();
        $customer->notify(new BusinessUpdateNotification(
            'Pembaruan baru',
            'Notifikasi ini belum dibaca.',
            'Kafe Contoh',
            url('/explore'),
        ));
        $otherCustomer->notify(new BusinessUpdateNotification(
            'Pembaruan pelanggan lain',
            'Notifikasi ini milik pelanggan lain.',
            'Kafe Contoh',
            url('/explore'),
        ));

        $this->actingAs($customer, 'customer')
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 1])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_customer_cannot_open_another_users_notification(): void
    {
        $recipient = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $otherUser->notify(new BusinessUpdateNotification(
            'Menu baru',
            'Pembaruan menu.',
            'Kafe Contoh',
            url('/explore'),
        ));
        $notification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($recipient, 'customer')
            ->post(route('notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_opening_an_order_notification_marks_it_read_and_opens_that_order(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $cafe = Cafe::factory()->create();
        $order = new Order([
            'public_code' => 'ORDERTEST01',
            'display_code' => 'WN-0001',
            'status' => Order::STATUS_DELIVERING,
            'fulfillment_method' => 'delivery',
        ]);
        $order->setRelation('cafe', $cafe);
        $customer->notify(new OrderReadyNotification($order));
        $notification = $customer->notifications()->firstOrFail();

        $this->actingAs($customer, 'customer')
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Tandai dibaca & buka pesanan')
            ->assertDontSee('Lihat riwayat pesanan');

        $this->actingAs($customer, 'customer')
            ->post(route('notifications.open', $notification->id))
            ->assertRedirect(route('orders.show', 'ORDERTEST01', false));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_opening_a_legacy_order_notification_from_an_old_host_uses_a_local_path(): void
    {
        config(['app.url' => 'https://wadahngopi.test']);
        $customer = User::factory()->create(['role' => 'user']);
        $cafe = Cafe::factory()->create();
        $order = new Order([
            'public_code' => 'ORDERTEST02',
            'display_code' => 'WN-0002',
            'status' => Order::STATUS_DELIVERING,
            'fulfillment_method' => 'delivery',
        ]);
        $order->setRelation('cafe', $cafe);
        $customer->notify(new OrderReadyNotification($order));
        $notification = $customer->notifications()->firstOrFail();
        $data = $notification->data;
        unset($data['order_code']);
        $data['url'] = 'https://previous-host.invalid/orders/ORDERTEST02';
        $notification->forceFill(['data' => $data])->save();

        $this->actingAs($customer, 'customer')
            ->post('/notifications/'.$notification->id.'/open')
            ->assertRedirect('/orders/ORDERTEST02');

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
