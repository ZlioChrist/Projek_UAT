<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\MerchantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_links_to_merchant_registration(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('AJUKAN USAHA')
            ->assertSee(route('merchant-registration.create'));
    }

    public function test_admin_cafe_registration_creates_a_pending_application_without_admin_access(): void
    {
        $city = City::factory()->create();
        $registration = $this->validRegistration($city, 'cafe');
        unset($registration['description']);

        $this->post(route('merchant-registration.store'), $registration)
            ->assertRedirect('/admin/login?mode=register');

        $user = User::where('email', 'new-owner@example.test')->firstOrFail();

        $this->assertSame('user', $user->role);
        $this->assertDatabaseHas('merchant_applications', [
            'user_id' => $user->id,
            'business_type' => 'cafe',
            'business_name' => 'Kedai Baru',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);
        $this->assertFalse($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
    }

    public function test_admin_roastery_registration_uses_only_the_roastery_application_type(): void
    {
        $city = City::factory()->create();

        $this->post(route('merchant-registration.store'), $this->validRegistration($city, 'roastery'))
            ->assertRedirect('/admin/login?mode=register');

        $this->assertDatabaseHas('merchant_applications', [
            'business_type' => 'roastery',
            'business_name' => 'Kedai Baru',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'new-owner@example.test',
            'role' => 'user',
        ]);
    }

    public function test_registering_a_new_city_adds_it_to_the_city_choices(): void
    {
        $samarinda = City::factory()->create(['name' => 'Samarinda', 'slug' => 'samarinda']);
        $registration = $this->validRegistration($samarinda, 'cafe');
        $registration['city_id'] = 'other';
        $registration['city_name'] = 'Balikpapan';

        $this->post(route('merchant-registration.store'), $registration)
            ->assertRedirect('/admin/login?mode=register');

        $city = City::query()->where('slug', 'balikpapan')->firstOrFail();
        $application = MerchantApplication::query()->where('business_name', 'Kedai Baru')->firstOrFail();

        $this->assertSame($city->id, $application->city_id);
        $this->get('/admin/login?mode=register')
            ->assertOk()
            ->assertSee('Balikpapan');
    }

    public function test_registration_city_choices_hide_uat_and_only_include_samarinda_or_submitted_cities(): void
    {
        City::factory()->create(['name' => 'Samarinda', 'slug' => 'samarinda']);
        City::factory()->create(['name' => 'Bontang', 'slug' => 'bontang']);
        City::factory()->create(['name' => 'Kota Simulasi UAT', 'slug' => 'kota-simulasi-uat']);
        $submittedCity = City::factory()->create(['name' => 'Balikpapan', 'slug' => 'balikpapan']);
        $customer = User::factory()->create(['role' => 'user']);

        MerchantApplication::create([
            'user_id' => $customer->id,
            'business_type' => 'cafe',
            'business_name' => 'Kafe Balikpapan',
            'city_id' => $submittedCity->id,
            'address' => 'Jalan Merdeka Nomor 10',
            'whatsapp_number' => '081234567890',
            'status' => MerchantApplication::STATUS_PENDING,
        ]);

        $this->get('/admin/login?mode=register')
            ->assertOk()
            ->assertSee('Samarinda')
            ->assertSee('Balikpapan')
            ->assertSee('Kota lainnya')
            ->assertDontSee('Bontang')
            ->assertDontSee('Kota Simulasi UAT');
    }

    public function test_registration_rejects_the_uat_city_as_a_selection_or_new_city(): void
    {
        $uatCity = City::factory()->create([
            'name' => 'Kota Simulasi UAT',
            'slug' => 'kota-simulasi-uat',
        ]);
        $registration = $this->validRegistration($uatCity, 'cafe');

        $this->post(route('merchant-registration.store'), $registration)
            ->assertRedirect('/admin/login?mode=register')
            ->assertSessionHasErrors('city_id');

        $registration['city_id'] = 'other';
        $registration['city_name'] = 'Kota Simulasi UAT';

        $this->post(route('merchant-registration.store'), $registration)
            ->assertRedirect('/admin/login?mode=register')
            ->assertSessionHasErrors('city_name');

        $this->assertDatabaseMissing('users', ['email' => 'new-owner@example.test']);
        $this->assertDatabaseCount('merchant_applications', 0);
    }

    public function test_registration_rejects_roles_outside_cafe_and_roastery(): void
    {
        $city = City::factory()->create();
        $registration = $this->validRegistration($city, 'developer');

        $this->post(route('merchant-registration.store'), $registration)
            ->assertRedirect('/admin/login?mode=register')
            ->assertSessionHasErrors('business_type');

        $this->get('/admin/login?mode=register')
            ->assertOk()
            ->assertSee('data-auth-mode="register"', false)
            ->assertSee('Admin Kafe')
            ->assertSee('Admin Roastery')
            ->assertSee('fi-wn-auth-required-mark', false)
            ->assertSee('Tentang usaha (opsional)');

        $this->assertDatabaseMissing('users', ['email' => 'new-owner@example.test']);
        $this->assertDatabaseCount('merchant_applications', 0);
    }

    /**
     * @return array<string, int|string>
     */
    private function validRegistration(City $city, string $businessType): array
    {
        return [
            'name' => 'Pemilik Baru',
            'email' => 'new-owner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_type' => $businessType,
            'business_name' => 'Kedai Baru',
            'city_id' => $city->id,
            'address' => 'Jalan Merdeka Nomor 10',
            'whatsapp_number' => '081234567890',
            'description' => 'Kopi lokal.',
        ];
    }
}
