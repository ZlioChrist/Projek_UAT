<?php

use App\Models\Cafe;
use App\Models\City;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->owner = User::factory()->create(['role' => 'admin']);
});

it('syncs current statuses and next-time labels for all published cafes and roasteries', function () {
    $openCafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->owner->id,
        'name' => 'Gudang Kena',
        'slug' => 'gudang-kena',
    ]);
    $closedCafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->owner->id,
        'name' => 'Kopi Maiko',
        'slug' => 'kopi-maiko',
    ]);
    $roastery = Roastery::factory()->create([
        'name' => 'Konus Roastery',
        'slug' => 'konus-roastery',
        'status' => 'published',
    ]);

    Http::fake(function ($request) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        if ($path === '/explore') {
            $cards = match ($query['activeLetter'] ?? '') {
                'G' => '<a href="https://wadahngopi.com/cafes/gudang-kena" class="cafe-card-2026"><div class="cafe-status-badge-smart open">BUKA <span>Sampai 00:00</span></div></a>',
                'K' => '<a href="https://wadahngopi.com/cafes/kopi-maiko" class="cafe-card-2026"><div class="cafe-status-badge-smart closed">TUTUP <span>Buka 16:00</span></div></a>',
                default => '',
            };

            return Http::response('<main class="explore-cafe-grid">'.$cards.'</main>', 200);
        }

        if ($path === '/roastery') {
            return Http::response(<<<'HTML'
                <main class="explore-cafe-grid">
                    <a href="https://wadahngopi.com/roastery/konus-roastery" class="cafe-card-2026">
                        <div class="cafe-status-badge-smart open">BUKA <span>Sampai 22:00</span></div>
                    </a>
                </main>
                HTML, 200);
        }

        return Http::response('', 404);
    });

    $this->artisan('app:sync-official-cafe-status')->assertExitCode(0);

    $openCafe->refresh();
    $closedCafe->refresh();
    $roastery->refresh();

    expect($openCafe->is_open)->toBeTrue()
        ->and($openCafe->official_status_text)->toBe('Sampai 00:00')
        ->and($openCafe->official_status_is_fresh)->toBeTrue()
        ->and($closedCafe->is_open)->toBeFalse()
        ->and($closedCafe->official_status_text)->toBe('Buka 16:00')
        ->and($roastery->is_open)->toBeTrue()
        ->and($roastery->official_status_text)->toBe('Sampai 22:00')
        ->and(Cafe::openNow()->pluck('id')->all())->toBe([$openCafe->id]);
});

it('preserves the previous snapshot when the official listing cannot be parsed', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->owner->id,
    ]);
    $cafe->forceFill([
        'official_is_open' => true,
        'official_status_text' => 'Sampai 23:00',
        'official_status_checked_at' => now(),
    ])->save();

    Http::fake([
        'wadahngopi.com/*' => Http::response('<main class="explore-cafe-grid"></main>', 200),
    ]);

    $this->artisan('app:sync-official-cafe-status')->assertExitCode(1);

    $cafe->refresh();

    expect($cafe->official_is_open)->toBeTrue()
        ->and($cafe->official_status_text)->toBe('Sampai 23:00');
});
