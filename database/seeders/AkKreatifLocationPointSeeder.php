<?php

namespace Database\Seeders;

use App\Models\CafeLocationPoint;
use Illuminate\Database\Seeder;

class AkKreatifLocationPointSeeder extends Seeder
{
    public function run(): void
    {
        CafeLocationPoint::updateOrCreate(
            ['slug' => 'ak-kreatif'],
            [
                'name' => 'AK Kreatif',
                'address' => 'Jl. KS Tubun Dalam No.38B, Dadi Mulya, Kec. Samarinda Ulu, Kota Samarinda, Kalimantan Timur 75123',
                'latitude' => -0.490124,
                'longitude' => 117.1399871,
                'google_maps_url' => 'https://www.google.com/maps/dir/?api=1&destination=-0.490124%2C117.1399871',
            ]
        );
    }
}
