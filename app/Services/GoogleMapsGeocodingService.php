<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleMapsGeocodingService
{
    /**
     * @return array{latitude: float, longitude: float, formatted_address: string}
     */
    public function geocode(string $address): array
    {
        $apiKey = config('services.google.maps_geocoding_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Pencarian alamat belum aktif. Admin perlu mengatur GOOGLE_MAPS_GEOCODING_API_KEY di file .env.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'components' => 'country:ID',
                    'language' => 'id',
                    'region' => 'id',
                    'key' => $apiKey,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Google Maps tidak dapat dihubungi. Periksa koneksi internet lalu coba lagi.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Layanan pencarian alamat sedang bermasalah. Coba lagi beberapa saat.');
        }

        $payload = $response->json();
        $status = is_array($payload) ? ($payload['status'] ?? null) : null;

        if ($status === 'ZERO_RESULTS') {
            throw new RuntimeException('Alamat tidak ditemukan. Coba tambahkan nama kota atau periksa ejaannya.');
        }

        if ($status !== 'OK') {
            throw new RuntimeException('Pencarian alamat gagal. Admin perlu memeriksa pengaturan Google Maps Geocoding.');
        }

        $result = $payload['results'][0] ?? null;
        $location = is_array($result) ? ($result['geometry']['location'] ?? null) : null;
        $latitude = is_array($location) ? ($location['lat'] ?? null) : null;
        $longitude = is_array($location) ? ($location['lng'] ?? null) : null;

        if (
            ! is_numeric($latitude)
            || ! is_numeric($longitude)
            || ! is_finite((float) $latitude)
            || ! is_finite((float) $longitude)
            || (float) $latitude < -90
            || (float) $latitude > 90
            || (float) $longitude < -180
            || (float) $longitude > 180
        ) {
            throw new RuntimeException('Google Maps mengembalikan koordinat yang tidak valid. Coba gunakan lokasi GPS.');
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'formatted_address' => (string) ($result['formatted_address'] ?? $address),
        ];
    }
}
