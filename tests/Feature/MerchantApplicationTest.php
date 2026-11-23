<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Cafe;
use App\Models\MerchantApplication;
use App\Models\Roastery;
use App\Models\User;
use App\Filament\Resources\MerchantApplicationResource\Pages\ListMerchantApplications;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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

    public function test_approved_cafe_and_roastery_applications_start_as_pending_businesses(): void
    {
        $developer = User::factory()->create(['role' => 'developer']);
        $cafeOwner = User::factory()->create(['role' => 'user']);
        $roasteryOwner = User::factory()->create(['role' => 'user']);
        $city = City::factory()->create();

        $cafeApplication = MerchantApplication::create([
            'user_id' => $cafeOwner->id,
            'business_type' => 'cafe',
            'business_name' => 'Kafe Pending',
            'city_id' => $city->id,
            'address' => 'Jalan Merdeka Nomor 10',
            'whatsapp_number' => '6281234567890',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);
        $roasteryApplication = MerchantApplication::create([
            'user_id' => $roasteryOwner->id,
            'business_type' => 'roastery',
            'business_name' => 'Roastery Pending',
            'city_id' => $city->id,
            'address' => 'Jalan Merdeka Nomor 12',
            'whatsapp_number' => '6281234567891',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($developer);

        Livewire::test(ListMerchantApplications::class)
            ->callTableAction('approve', $cafeApplication)
            ->callTableAction('approve', $roasteryApplication);

        $this->assertDatabaseHas('cafes', [
            'name' => 'Kafe Pending',
            'owner_id' => $cafeOwner->id,
            'status' => 'review',
        ]);
        $this->assertDatabaseHas('roasteries', [
            'name' => 'Roastery Pending',
            'owner_id' => $roasteryOwner->id,
            'status' => 'review',
        ]);
        $this->assertSame('admin', $cafeOwner->fresh()->role);
        $this->assertSame('roastery', $roasteryOwner->fresh()->role);
    }

    public function test_new_cafe_and_roastery_records_default_to_pending_without_a_status(): void
    {
        $cafe = Cafe::factory()->create(['status' => null]);
        $roastery = Roastery::factory()->create(['status' => null]);

        $this->assertSame('review', $cafe->status);
        $this->assertSame('review', $roastery->status);
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
