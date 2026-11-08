<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BusinessUpdateNotification;
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
}
