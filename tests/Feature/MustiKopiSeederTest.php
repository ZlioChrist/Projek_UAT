<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\MustiKopiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MustiKopiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_musti_kopi_with_owner_hours_and_visible_menu_without_images(): void
    {
        $seeder = new MustiKopiSeeder;
        $seeder->run();
        $seeder->run();

        $owner = User::where('email', 'kopimust@wadahngopi.com')->firstOrFail();
        $cafe = Cafe::where('slug', 'kopi-must')->firstOrFail();
        $items = MenuItem::query()->where('cafe_id', $cafe->id);

        $this->assertSame('Kopi Must', $owner->name);
        $this->assertSame('Kopi Must', $cafe->name);
        $this->assertSame('admin', $owner->role);
        $this->assertSame($owner->id, $cafe->owner_id);
        $this->assertSame('review', $cafe->status);
        $this->assertSame('Samarinda', $cafe->city->name);
        $this->assertSame('15:00', $cafe->operating_hours['weekday']['open']);
        $this->assertSame('23:00', $cafe->operating_hours['weekday']['close']);
        $this->assertSame('15:00', $cafe->operating_hours['weekend']['open']);
        $this->assertSame('23:00', $cafe->operating_hours['weekend']['close']);
        $this->assertSame(27, (clone $items)->count());
        $this->assertSame(27, (clone $items)->where('stock', 100)->count());
        $this->assertSame(27, (clone $items)->where('is_active', true)->count());
        $this->assertSame(0, (clone $items)->whereNotNull('image_path')->count());

        $this->assertEquals(10000, (float) MenuItem::where('cafe_id', $cafe->id)->where('name', 'Kopi Item (R)')->firstOrFail()->price);
        $this->assertEquals(20000, (float) MenuItem::where('cafe_id', $cafe->id)->where('name', 'Kopi Susu (L)')->firstOrFail()->price);
        $this->assertEquals(5000, (float) MenuItem::where('cafe_id', $cafe->id)->where('name', 'Es Teh (R)')->firstOrFail()->price);
        $this->assertEquals(23000, (float) MenuItem::where('cafe_id', $cafe->id)->where('name', 'Cookies & Cream (L)')->firstOrFail()->price);
    }
}
