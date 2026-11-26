<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Facility;
use Illuminate\Support\Str;

class CafeVoiceSearchService
{
    /**
     * @var array<string, array{label: string, aliases: list<string>}>
     */
    private const FEATURE_CATALOG = [
        'wifi' => [
            'label' => 'Wi-Fi',
            'aliases' => ['wifi', 'wi fi', 'wfc friendly', 'internet'],
        ],
        'mushola' => [
            'label' => 'Mushola',
            'aliases' => ['mushola', 'musholla', 'musala', 'musalla', 'tempat sholat', 'tempat salat', 'prayer room'],
        ],
        'parking' => [
            'label' => 'Parkir',
            'aliases' => ['parkir', 'parking'],
        ],
        'ac' => [
            'label' => 'AC',
            'aliases' => ['ac', 'ber ac', 'air conditioner', 'pendingin ruangan'],
        ],
        'outdoor' => [
            'label' => 'Area outdoor',
            'aliases' => ['outdoor', 'outdoor seating', 'tempat duduk luar'],
        ],
        'socket' => [
            'label' => 'Colokan listrik',
            'aliases' => ['colokan', 'stop kontak', 'socket', 'electric socket'],
        ],
        'open_24_hours' => [
            'label' => 'Buka 24 jam',
            'aliases' => ['24 jam', 'dua puluh empat jam'],
        ],
        'smoking_area' => [
            'label' => 'Smoking area',
            'aliases' => ['smoking area', 'area merokok'],
        ],
        'live_music' => [
            'label' => 'Live music',
            'aliases' => ['live music', 'musik langsung'],
        ],
        'pet_friendly' => [
            'label' => 'Pet friendly',
            'aliases' => ['pet friendly', 'boleh bawa hewan', 'ramah hewan'],
        ],
    ];

    /**
     * @return list<array{key: string, label: string, aliases: list<string>}>
     */
    public function requestedFeatures(string $command): array
    {
        $normalizedCommand = $this->normalize($command);
        $features = [];

        foreach (self::FEATURE_CATALOG as $key => $feature) {
            $aliases = array_map(fn (string $alias): string => $this->normalize($alias), $feature['aliases']);
            if ($this->containsRequestedAlias($normalizedCommand, $aliases)) {
                $features[] = ['key' => $key, ...$feature];
            }
        }

        $knownAliases = collect(self::FEATURE_CATALOG)
            ->flatMap(fn (array $feature): array => array_map(
                fn (string $alias): string => $this->normalize($alias),
                $feature['aliases']
            ))
            ->unique()
            ->all();

        foreach (Facility::query()->select('name')->distinct()->pluck('name') as $facilityName) {
            $normalizedName = $this->normalize((string) $facilityName);
            if ($normalizedName === '' || in_array($normalizedName, $knownAliases, true)) {
                continue;
            }

            if ($this->containsRequestedAlias($normalizedCommand, [$normalizedName])) {
                $features[] = [
                    'key' => 'custom_'.sha1($normalizedName),
                    'label' => (string) $facilityName,
                    'aliases' => [$normalizedName],
                ];
            }
        }

        return collect($features)
            ->unique('key')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{key: string, label: string, aliases: list<string>}>  $requestedFeatures
     * @return array{matched: list<string>, missing: list<string>}
     */
    public function matchFeatures(Cafe $cafe, array $requestedFeatures): array
    {
        $recordedFacilities = $cafe->facilities
            ->pluck('name')
            ->map(fn (string $name): string => $this->normalize($name))
            ->all();
        $matched = [];
        $missing = [];

        foreach ($requestedFeatures as $feature) {
            $isRecorded = $feature['key'] === 'wifi' && (bool) $cafe->has_wifi;

            foreach ($feature['aliases'] as $alias) {
                $normalizedAlias = $this->normalize($alias);
                foreach ($recordedFacilities as $facility) {
                    if ($facility === $normalizedAlias || str_contains($facility, $normalizedAlias)) {
                        $isRecorded = true;
                        break 2;
                    }
                }
            }

            if ($isRecorded) {
                $matched[] = $feature['label'];
            } else {
                $missing[] = $feature['label'];
            }
        }

        return ['matched' => $matched, 'missing' => $missing];
    }

    public function budgetFromCommand(string $command, int $fallback): int
    {
        if (! preg_match(
            '/(?:(?:harga|budget)\s*(?:(?:maksimal|maksimum|dibawah|di\s+bawah|kurang\s+dari)\s*)?|(?:maksimal|maksimum|dibawah|di\s+bawah|kurang\s+dari|rp)\s*)(?:rp\s*)?(\d{1,3}(?:[.,]\d{3})+|\d{1,6})\s*(ribu|rb|k|rupiah)?\b/i',
            $command,
            $matches
        )) {
            return $fallback;
        }

        $amount = (int) str_replace([',', '.'], '', $matches[1]);
        if (in_array(strtolower($matches[2] ?? ''), ['ribu', 'rb', 'k'], true)) {
            $amount *= 1000;
        }

        return min(max(0, $amount), 1_000_000);
    }

    private function containsRequestedAlias(string $normalizedCommand, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if ($alias === '' || ! str_contains(' '.$normalizedCommand.' ', ' '.$alias.' ')) {
                continue;
            }

            $escapedAlias = preg_quote($alias, '/');
            if (preg_match('/(?:^|\s)(?:tidak(?:\s+(?:ada|punya))?|tanpa|nggak|enggak|gak|ga)(?:\s+(?:perlu|butuh))?\s+'.$escapedAlias.'(?:\s|$)/', $normalizedCommand)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $ascii = Str::ascii(mb_strtolower($value));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '');
    }
}
