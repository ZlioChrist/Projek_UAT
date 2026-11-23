<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class ExternalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_can_create_a_verified_customer_but_never_an_admin_account(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'https://wadahngopi.test/auth/google/callback',
        ]);
        session(['auth.google.audience' => 'customer']);
        $this->mockGoogleUser([
            'sub' => 'google-customer-1',
            'email' => 'coffee.customer@example.test',
            'email_verified' => true,
            'name' => 'Google Customer',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('account'));

        $customer = User::where('email', 'coffee.customer@example.test')->firstOrFail();
        $this->assertSame('user', $customer->role);
        $this->assertSame('google-customer-1', $customer->google_id);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_customer_login_rejects_an_unverified_email(): void
    {
        session(['auth.google.audience' => 'customer']);
        $this->mockGoogleUser([
            'sub' => 'google-unverified-1',
            'email' => 'unverified@example.test',
            'email_verified' => false,
            'name' => 'Unverified',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('auth');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest('customer');
    }

    public function test_google_admin_login_only_uses_an_existing_admin_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'existing.admin@example.test',
            'role' => 'admin',
        ]);
        session(['auth.google.audience' => 'admin']);
        $this->mockGoogleUser([
            'sub' => 'google-admin-1',
            'email' => 'existing.admin@example.test',
            'email_verified' => true,
            'name' => 'Existing Admin',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertSame('google-admin-1', $admin->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_does_not_create_an_admin_account(): void
    {
        session(['auth.google.audience' => 'admin']);
        $this->mockGoogleUser([
            'sub' => 'google-unregistered-admin-1',
            'email' => 'new.admin@example.test',
            'email_verified' => true,
            'name' => 'Unregistered Admin',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('auth');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest('web');
    }

    public function test_customer_can_login_with_a_verified_whatsapp_otp(): void
    {
        $customer = User::factory()->create([
            'role' => 'user',
            'phone_number' => '6281234567890',
            'phone_verified_at' => now(),
        ]);
        $code = null;
        $this->configureMetaWhatsApp(function ($request) use (&$code) {
            $code = data_get($request->data(), 'template.components.0.parameters.0.text');

            return Http::response(['messages' => [['id' => 'wamid.test']]], 200);
        });

        $this->post(route('auth.whatsapp.login.request'), [
            'phone_number' => '081234567890',
        ])->assertRedirect()->assertSessionHas('whatsapp_login_sent', true);

        $this->assertSame(6, strlen((string) $code));
        $this->post(route('auth.whatsapp.login.verify'), [
            'phone_number' => '081234567890',
            'code' => $code,
        ])->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertFalse(Auth::guard('web')->check());
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/123456789012345/messages')
            && data_get($request->data(), 'to') === '6281234567890'
            && data_get($request->data(), 'template.components.0.parameters.0.text')
                === data_get($request->data(), 'template.components.1.parameters.0.text'));
    }

    public function test_admin_whatsapp_otp_only_authenticates_an_existing_authorized_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'owner_cafe',
            'phone_number' => '6281234567890',
            'phone_verified_at' => now(),
        ]);
        $code = null;
        $this->configureMetaWhatsApp(function ($request) use (&$code) {
            $code = data_get($request->data(), 'template.components.0.parameters.0.text');

            return Http::response(['messages' => [['id' => 'wamid.test']]], 200);
        });

        $this->post(route('owner.auth.whatsapp.login.request'), [
            'phone_number' => '081234567890',
        ])->assertRedirect()->assertSessionHas('whatsapp_login_sent', true);
        $this->post(route('owner.auth.whatsapp.login.verify'), [
            'phone_number' => '081234567890',
            'code' => $code,
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_unknown_whatsapp_login_number_gets_the_same_generic_response_without_sending_a_code(): void
    {
        Http::fake();

        $this->post(route('auth.whatsapp.login.request'), [
            'phone_number' => '081234567890',
        ])->assertRedirect()->assertSessionHas('whatsapp_login_sent', true);

        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();
    }

    public function test_whatsapp_otp_is_rejected_after_five_incorrect_attempts(): void
    {
        $customer = User::factory()->create([
            'role' => 'user',
            'phone_number' => '6281234567890',
            'phone_verified_at' => now(),
        ]);
        $this->configureMetaWhatsApp(fn () => Http::response(['messages' => [['id' => 'wamid.test']]], 200));

        $this->post(route('auth.whatsapp.login.request'), [
            'phone_number' => '081234567890',
        ])->assertRedirect();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('auth.whatsapp.login.verify'), [
                'phone_number' => '081234567890',
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $this->assertGuest('customer');
        $this->assertSame('6281234567890', $customer->fresh()->phone_number);
    }

    public function test_whatsapp_otp_expires_after_five_minutes(): void
    {
        $customer = User::factory()->create([
            'role' => 'user',
            'phone_number' => '6281234567890',
            'phone_verified_at' => now(),
        ]);
        $this->configureMetaWhatsApp(fn () => Http::response(['messages' => [['id' => 'wamid.test']]], 200));

        $this->post(route('auth.whatsapp.login.request'), [
            'phone_number' => '081234567890',
        ])->assertRedirect();
        $this->travel(6)->minutes();

        $this->post(route('auth.whatsapp.login.verify'), [
            'phone_number' => '081234567890',
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->assertGuest('customer');
        $this->assertSame('6281234567890', $customer->fresh()->phone_number);
    }

    public function test_customer_can_link_a_phone_only_after_successful_otp_verification(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $code = null;
        $this->configureMetaWhatsApp(function ($request) use (&$code) {
            $code = data_get($request->data(), 'template.components.0.parameters.0.text');

            return Http::response(['messages' => [['id' => 'wamid.test']]], 200);
        });

        $this->actingAs($customer, 'customer')
            ->post(route('account.whatsapp.request'), ['phone_number' => '+62 812-3456-7890'])
            ->assertRedirect()
            ->assertSessionHas('whatsapp_link_sent', true);

        $this->assertNull($customer->fresh()->phone_number);
        $this->actingAs($customer, 'customer')
            ->post(route('account.whatsapp.verify'), [
                'phone_number' => '081234567890',
                'code' => $code,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Nomor WhatsApp berhasil diverifikasi dan ditautkan.');

        $this->assertSame('6281234567890', $customer->fresh()->phone_number);
        $this->assertNotNull($customer->fresh()->phone_verified_at);
    }

    private function mockGoogleUser(array $attributes): void
    {
        $googleUser = (new GoogleUser)
            ->setRaw($attributes)
            ->map([
                'id' => $attributes['sub'],
                'email' => $attributes['email'],
                'name' => $attributes['name'],
            ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function configureMetaWhatsApp(callable $response): void
    {
        config([
            'services.meta_whatsapp.access_token' => 'test-only-access-token',
            'services.meta_whatsapp.phone_number_id' => '123456789012345',
            'services.meta_whatsapp.otp_template' => 'authentication_code',
            'services.meta_whatsapp.graph_version' => 'v23.0',
        ]);
        Http::fake($response);
    }
}
