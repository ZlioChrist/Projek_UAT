<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\MerchantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_apply_to_register_a_cafe_for_developer_review(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $city = City::factory()->create();

        $this->actingAs($customer, 'customer')
            ->post(route('merchant-applications.store'), [
                'business_type' => 'cafe',
                'business_name' => 'Kafe Pengajuan',
                'city_id' => $city->id,
                'address' => 'Jalan Merdeka Nomor 10',
                'whatsapp_number' => '081234567890',
                'description' => 'Kopi dan makanan lokal.',
            ])
            ->assertRedirect(route('account'));

        $this->assertDatabaseHas('merchant_applications', [
            'user_id' => $customer->id,
            'business_type' => 'cafe',
            'business_name' => 'Kafe Pengajuan',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);
        $this->assertSame('user', $customer->fresh()->role);
    }

    public function test_shop_operators_can_browse_the_main_site_separately_from_their_panel(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)
            ->get(route('explore'))
            ->assertOk();

        $this->get(route('owner.dashboard'))->assertRedirect('/admin');
    }

    public function test_admin_session_does_not_authenticate_customer_account_routes(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner, 'web')
            ->get(route('account'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }

    public function test_customer_can_still_access_the_public_website(): void
    {
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer, 'customer')
            ->get(route('explore'))
            ->assertOk();
    }

    public function test_customer_can_login_while_admin_remains_logged_in(): void
    {
        $owner = User::factory()->create([
            'email' => 'cafe-owner@example.test',
            'role' => 'admin',
        ]);
        $customer = User::factory()->create([
            'email' => 'customer@example.test',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
        $this->actingAs($owner, 'web')
            ->get(route('home'))
            ->assertOk();

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($owner, 'web');
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->get(route('account'))
            ->assertOk()
            ->assertSee($customer->email)
            ->assertDontSee($owner->email);

        $this->post(route('logout'))
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($owner, 'web');
        $this->assertGuest('customer');
    }
}
