<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\UatDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class UatDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_demo_data_is_labeled_safe_and_idempotent(): void
    {
        Storage::fake('public');
        $seeder = new UatDemoSeeder;
        $seeder->run();
        $seeder->run();

        $demoCafe = Cafe::where('slug', 'uat-demo-cafe')->firstOrFail();
        $customer = User::where('email', 'customer@uat-demo.test')->firstOrFail();
        $availableItem = MenuItem::where('cafe_id', $demoCafe->id)
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertSame(2, Cafe::whereIn('slug', ['uat-demo-cafe', 'uat-demo-pembanding'])->count());
        $this->assertSame(2, MenuItem::where('cafe_id', $demoCafe->id)->count());
        $this->assertSame('user', $customer->role);
        $this->assertStringContainsString('BUKAN QRIS', Storage::disk('public')->get('uat-demo/qris-bukan-pembayaran.svg'));
        $this->assertStringContainsString('JANGAN DIPINDAI', Storage::disk('public')->get('uat-demo/qris-bukan-pembayaran.svg'));
        $this->assertSame('000000000000', $demoCafe->bank_account_number);
        $this->assertStringContainsString('JANGAN TRANSFER', $demoCafe->bank_account_name);

        $this->get(route('cafes.show', $demoCafe->slug))
            ->assertOk()
            ->assertSee('Kopi Demo UAT')
            ->assertDontSee('Roti Demo Habis')
            ->assertDontSee('Kopi Pembanding UAT');

        $this->actingAs($customer, 'customer')
            ->withSession(['order_cart' => ['cafe_id' => $demoCafe->id, 'items' => [$availableItem->id => 1]]])
            ->get(route('checkout.show', $demoCafe->slug))
            ->assertOk()
            ->assertSee('DATA DUMMY UAT - BUKAN QRIS')
            ->assertSee('MELAKUKAN PEMBAYARAN')
            ->assertSee('000000000000')
            ->assertSee('JANGAN TRANSFER');
    }

    public function test_uat_demo_seeder_refuses_non_local_environments(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UAT demo data may only be seeded in local or testing environments.');

        (new UatDemoSeeder)->run();
    }
}
