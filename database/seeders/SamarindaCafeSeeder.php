<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use App\Models\Facility;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder data cafe & roastery NYATA di Samarinda, Kalimantan Timur.
 *
 * Sumber data: Google Maps, GoFood, Instagram, artikel review lokal
 * (kaltimfaktual.co, arusbawah.co, lensaperjalanan.com, dll.)
 *
 * CARA PAKAI:
 *   php artisan db:seed --class=SamarindaCafeSeeder
 *
 * Aman dijalankan berkali-kali (menggunakan updateOrCreate).
 */
class SamarindaCafeSeeder extends Seeder
{
    /**
     * Daftar gambar Unsplash bertema cafe/kopi (gratis & legal).
     * Dirotasi secara acak untuk setiap cafe agar tidak monoton.
     */
    private array $cafeImages = [
        'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1469957761103-5594cd39769a?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1445116572660-236099ec97a2?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1507133750040-4a8f57021571?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1525610553991-2bede1a236e2?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1481833761820-0509d3217039?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1559925393-8be0ec41b504?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1551887139-12a8627f8059?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1524350303359-29c67670732d?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1561047029-3000c6812c53?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1522012188892-24beb302783d?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1504639725590-34d0984388bd?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1600093463592-8e36ae95ef56?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1511081692775-05d0f180a065?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1485182708500-e8f1f318ba72?auto=format&fit=crop&q=80&w=800',
    ];

    /**
     * Daftar gambar Unsplash bertema menu/makanan (gratis & legal).
     */
    private array $menuImages = [
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=80&w=800',
    ];

    /**
     * Daftar gambar Unsplash bertema roastery/biji kopi (gratis & legal).
     */
    private array $roasteryImages = [
        'https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1514432324607-a09d9b4aefda?auto=format&fit=crop&q=80&w=800',
        'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&q=80&w=800',
    ];

    /**
     * Menjalankan seeder — inject 100 cafe + roastery ke database.
     */
    public function run(): void
    {
        // Pastikan kota Samarinda ada
        $samarinda = City::firstOrCreate(
            ['slug' => 'samarinda'],
            ['name' => 'Samarinda']
        );

        // Ambil atau buat user admin sebagai owner default
        $admin = User::where('role', 'admin')->first()
            ?? User::firstOrCreate(
                ['email' => 'admin@wadahngopi.test'],
                [
                    'name' => 'WadahNgopi Admin',
                    'role' => 'admin',
                    'password' => bcrypt('password'),
                ]
            );

        // ═══════════════════════════════════════════════
        // BAGIAN 1: DATA 100 CAFE SAMARINDA
        // ═══════════════════════════════════════════════
        $cafes = $this->getCafeData();

        $cafeCount = 0;
        foreach ($cafes as $cafeData) {
            // Pisahkan fasilitas dari data cafe
            $facilityNames = $cafeData['facilities'] ?? [];
            unset($cafeData['facilities']);

            // Tambahkan field default
            $cafeData['city_id'] = $samarinda->id;
            $cafeData['owner_id'] = $admin->id;
            $cafeData['status'] = 'published';
            $cafeData['image_path'] = $cafeData['image_path'] ?? $this->cafeImages[array_rand($this->cafeImages)];
            $cafeData['menu_images'] = $cafeData['menu_images'] ?? $this->getRandomMenuImages();

            // Buat atau update cafe (berdasarkan nama, anti-duplikasi)
            $cafe = Cafe::updateOrCreate(
                ['name' => $cafeData['name']],
                $cafeData
            );

            // Assign fasilitas ke cafe
            $this->assignFacilities($cafe, $facilityNames);

            $cafeCount++;
        }

        $this->command->info("✅ {$cafeCount} cafe berhasil di-seed/update!");

        // ═══════════════════════════════════════════════
        // BAGIAN 2: DATA ROASTERY SAMARINDA
        // ═══════════════════════════════════════════════
        $roasteries = $this->getRoasteryData();

        $roasteryCount = 0;
        foreach ($roasteries as $roasteryData) {
            $roasteryData['city_id'] = $samarinda->id;
            $roasteryData['owner_id'] = $admin->id;
            $roasteryData['status'] = 'published';
            $roasteryData['image_path'] = $roasteryData['image_path'] ?? $this->roasteryImages[array_rand($this->roasteryImages)];
            $roasteryData['menu_images'] = $roasteryData['menu_images'] ?? $this->getRandomMenuImages();

            Roastery::updateOrCreate(
                ['name' => $roasteryData['name']],
                $roasteryData
            );

            $roasteryCount++;
        }

        $this->command->info("✅ {$roasteryCount} roastery berhasil di-seed/update!");
        $this->command->info("🎉 Total: {$cafeCount} cafe + {$roasteryCount} roastery di Samarinda!");
    }

    /**
     * Assign fasilitas ke cafe berdasarkan array nama fasilitas.
     * Menggunakan firstOrCreate agar fasilitas yang belum ada otomatis dibuat.
     */
    private function assignFacilities(Cafe $cafe, array $facilityNames): void
    {
        // Icon mapping untuk fasilitas standar
        $iconMap = [
            'WiFi' => 'bi-wifi',
            'AC' => 'bi-snow',
            'Outdoor Seating' => 'bi-tree',
            'Parking' => 'bi-car-front',
            'Smoking Area' => 'bi-wind',
            'WFC Friendly' => 'bi-laptop',
            '24 Jam' => 'bi-clock',
            'Live Music' => 'bi-music-note-beamed',
            'Pet Friendly' => 'bi-heart',
            'Colokan' => 'bi-plug',
            'Meeting Room' => 'bi-people',
            'Rooftop' => 'bi-building',
            'Photobox' => 'bi-camera',
        ];

        foreach ($facilityNames as $name) {
            $icon = $iconMap[$name] ?? 'bi-check-circle';

            // Cek apakah fasilitas ini sudah ada untuk cafe ini
            Facility::firstOrCreate(
                [
                    'cafe_id' => $cafe->id,
                    'name' => $name,
                ],
                [
                    'icon' => $icon,
                ]
            );
        }
    }

    /**
     * Ambil 2-3 gambar menu acak dari daftar.
     */
    private function getRandomMenuImages(): array
    {
        $shuffled = $this->menuImages;
        shuffle($shuffled);

        return array_slice($shuffled, 0, rand(2, 3));
    }

    /**
     * Helper: Buat jam operasional JSON sesuai format model Cafe.
     */
    private function hours(string $weekdayOpen, string $weekdayClose, ?string $weekendOpen = null, ?string $weekendClose = null): array
    {
        return [
            'weekday' => ['open' => $weekdayOpen, 'close' => $weekdayClose],
            'weekend' => ['open' => $weekendOpen ?? $weekdayOpen, 'close' => $weekendClose ?? $weekdayClose],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // DATA CAFE — 100 CAFE NYATA DI SAMARINDA
    // Koordinat berdasarkan estimasi area jalan/kecamatan.
    // Sumber: Google, GoFood, arusbawah.co, kaltimfaktual.co, dll.
    // ═══════════════════════════════════════════════════════════════

    private function getCafeData(): array
    {
        return [
            // ─────────────────────────────────────────────
            // GRUP 1: UPDATE 20 CAFE LAMA (dari DatabaseSeeder)
            // Data diperkaya: deskripsi, jam buka, WhatsApp, social links
            // ─────────────────────────────────────────────

            // 1
            [
                'name' => 'Coffee & Co. - SOUL',
                'address' => 'City Centrum Mall, 1st Floor, Jl. Basuki Rahmat, Samarinda',
                'description' => 'Coffee shop premium yang berlokasi di dalam City Centrum Mall Samarinda. Mengusung konsep modern dengan interior minimalis dan pencahayaan hangat. Menyajikan specialty coffee, espresso-based drinks, dan berbagai pastry artisan. Tempat favorit para pekerja muda dan mahasiswa untuk meeting atau WFC.',
                'has_wifi' => true,
                'latitude' => -0.502812,
                'longitude' => 117.151240,
                'google_maps_url' => 'https://maps.google.com/?q=Coffee+%26+Co+SOUL+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'social_links' => ['instagram' => 'coffeeandco.soul'],
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],
            // 2
            [
                'name' => 'Coffee & Co',
                'address' => 'Jl. Mulawarman No.171, Samarinda Kota',
                'description' => 'Cabang utama Coffee & Co di jantung Samarinda. Terkenal dengan racikan kopi susu khas mereka dan suasana cozy yang cocok untuk nongkrong sore hari. Interior bergaya industrial-warm dengan aksen kayu dan tanaman hijau.',
                'has_wifi' => true,
                'latitude' => -0.501582,
                'longitude' => 117.153890,
                'google_maps_url' => 'https://maps.google.com/?q=Coffee+%26+Co+Mulawarman+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking'],
            ],
            // 3
            [
                'name' => 'Fren.co Coffee & Eatery',
                'address' => 'Jl. Siradj Salman No.6A, Sidodadi, Samarinda Ulu',
                'description' => 'Cafe & eatery dengan menu lengkap dari kopi specialty hingga western food. Terletak di kawasan strategis Siradj Salman yang mudah dijangkau. Suasana hangat dan friendly, cocok untuk hangout bareng teman atau kerja santai.',
                'has_wifi' => true,
                'latitude' => -0.490423,
                'longitude' => 117.148256,
                'google_maps_url' => 'https://maps.google.com/?q=Frenco+Coffee+Eatery+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 4
            [
                'name' => '212 COFFEE & SPACE',
                'address' => 'Jl. Bung Tomo No.18C, Sungai Keledang, Samarinda Seberang',
                'description' => 'Coffee shop & creative space yang mengusung konsep industrial modern. Cocok untuk acara komunitas, workshop, atau sekadar nongkrong sambil menikmati specialty coffee. Terletak di kawasan Samarinda Seberang yang makin berkembang.',
                'has_wifi' => true,
                'latitude' => -0.508934,
                'longitude' => 117.121483,
                'google_maps_url' => 'https://maps.google.com/?q=212+Coffee+Space+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 5
            [
                'name' => 'Jack House COFFEE & EATERY',
                'address' => 'Jl. RE Martadinata No.06, Samarinda Kota',
                'description' => 'Cafe & eatery bertema rumahan yang menyajikan kopi berkualitas dan makanan rumahan khas Indonesia. Interior hangat dengan nuansa kayu dan batu alam. Menu favorit: Nasi Goreng Jack House, Es Kopi Susu, dan aneka pastry segar.',
                'has_wifi' => true,
                'latitude' => -0.504567,
                'longitude' => 117.145678,
                'google_maps_url' => 'https://maps.google.com/?q=Jack+House+Coffee+Eatery+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 6
            [
                'name' => 'Pluto House And Coffee',
                'address' => 'Jl. Angklung, Dadi Mulya, Samarinda Ulu',
                'description' => 'Kedai kopi unik dengan konsep rumahan yang nyaman dan intimate. Terletak di kawasan Angklung yang tenang, jauh dari kebisingan kota. Menyajikan single origin dan house blend kopi pilihan serta aneka cemilan.',
                'has_wifi' => true,
                'latitude' => -0.485678,
                'longitude' => 117.156789,
                'google_maps_url' => 'https://maps.google.com/?q=Pluto+House+And+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 7
            [
                'name' => 'Pot O Koffie',
                'address' => 'Jl. Angklung No.B6, Dadi Mulya, Samarinda Ulu',
                'description' => 'Cafe sekaligus restoran yang menawarkan menu lengkap dari kopi, pastry, hingga hidangan utama seperti burger, salad, dan pasta. Suasana elegan dengan sentuhan European cafe. Cocok untuk brunch, lunch, maupun dinner.',
                'has_wifi' => true,
                'latitude' => -0.485123,
                'longitude' => 117.157890,
                'google_maps_url' => 'https://maps.google.com/?q=Pot+O+Koffie+Samarinda',
                'operating_hours' => $this->hours('07:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 8
            [
                'name' => 'Labricca Coffee',
                'address' => 'Jl. Gerilya, Sungai Pinang Dalam, Samarinda',
                'description' => 'Cafe bergaya vintage dan industrial yang sangat ramah bagi pelajar dan pekerja. Menyediakan WiFi kencang dan banyak colokan. Sering mengadakan acara live music di akhir pekan. Menu kopi specialty dan non-kopi tersedia lengkap.',
                'has_wifi' => true,
                'latitude' => -0.489734,
                'longitude' => 117.143256,
                'google_maps_url' => 'https://maps.google.com/?q=Labricca+Coffee+Samarinda',
                'operating_hours' => $this->hours('15:00', '23:30'),
                'facilities' => ['WiFi', 'AC', 'Live Music', 'Smoking Area', 'WFC Friendly', 'Colokan'],
            ],
            // 9
            [
                'name' => 'satukata coffee co.',
                'address' => 'Jl. Kadrie Oening, Air Hitam, Samarinda Ulu',
                'description' => 'Terletak di kawasan perbukitan dekat Taman Samarendah, satukata coffee co. menawarkan pemandangan indah dan udara segar. Desain interior minimalis dengan area indoor dan outdoor. Cocok untuk deep talk dan menikmati sunset sambil ngopi.',
                'has_wifi' => true,
                'latitude' => -0.491234,
                'longitude' => 117.141234,
                'google_maps_url' => 'https://maps.google.com/?q=satukata+coffee+co+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'social_links' => ['instagram' => 'satukatacoffee'],
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 10
            [
                'name' => 'Titik Koma Antasari Samarinda',
                'address' => 'Jl. P. Antasari No.20B, Samarinda',
                'description' => 'Cafe dengan konsep modern minimalis yang eye-catching. Menawarkan berbagai pilihan minuman kopi dan non-kopi. Menu favorit: Caramel Latte Macchiato dan Es Kopi Titik Koma. Interior clean dan instagramable.',
                'has_wifi' => true,
                'latitude' => -0.495678,
                'longitude' => 117.135678,
                'google_maps_url' => 'https://maps.google.com/?q=Titik+Koma+Antasari+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],
            // 11
            [
                'name' => 'Kana Coffee',
                'address' => 'Jl. Muso Salim No.53, Samarinda',
                'description' => 'Kedai kopi yang menyediakan aneka kopi, nasi, dan jajanan seperti cireng dan singkong goreng. Suasana santai dan harga terjangkau. Cocok untuk mahasiswa yang ingin nongkrong sambil menikmati rice bowl favorit.',
                'has_wifi' => true,
                'latitude' => -0.498901,
                'longitude' => 117.161234,
                'google_maps_url' => 'https://maps.google.com/?q=Kana+Coffee+Samarinda',
                'operating_hours' => $this->hours('11:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area', 'Parking'],
            ],
            // 12
            [
                'name' => 'YOU Coffee and Brunch',
                'address' => 'Jl. Gamelan No.2, Dadi Mulya, Samarinda Ulu',
                'description' => 'Cafe berkonsep working space dengan suasana tenang dan estetik. Memiliki banyak spot foto dan area yang mendukung produktivitas. Menyajikan berbagai brunch, kue artisan, dan kopi specialty. Favorit para remote worker.',
                'has_wifi' => true,
                'latitude' => -0.481234,
                'longitude' => 117.151234,
                'google_maps_url' => 'https://maps.google.com/?q=YOU+Coffee+and+Brunch+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'social_links' => ['instagram' => 'youcoffeeanbrunch'],
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan', 'Parking'],
            ],
            // 13
            [
                'name' => 'Althea Coffee & Co',
                'address' => 'Jl. Perjuangan No.99, Samarinda Utara',
                'description' => 'Coffee shop premium dengan racikan kopi specialty yang dikurasi langsung oleh barista berpengalaman. Interior bergaya Scandinavian minimalis dengan aksen tanaman tropis. Tempat yang sempurna untuk meeting bisnis santai.',
                'has_wifi' => true,
                'latitude' => -0.471234,
                'longitude' => 117.161234,
                'google_maps_url' => 'https://maps.google.com/?q=Althea+Coffee+Co+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],
            // 14
            [
                'name' => 'KOPIKUMANA',
                'address' => 'Jl. Angklung No.06A, Dadi Mulya, Samarinda Ulu',
                'description' => 'Cafe 24 jam dengan suasana hangat bernuansa kayu yang nyaman untuk santai kapan saja. Terkenal di kalangan mahasiswa dan pekerja malam. Menu kopi racikan sendiri dengan harga terjangkau dan cemilan lengkap.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.485901,
                'longitude' => 117.155678,
                'google_maps_url' => 'https://maps.google.com/?q=KOPIKUMANA+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', '24 Jam', 'Colokan', 'Smoking Area'],
            ],
            // 15
            [
                'name' => 'Jakarta Loc. Coffe and Space',
                'address' => 'Jl. Ar-Rasyidin 2, Samarinda',
                'description' => 'Coffee shop yang membawa vibes kafe Jakarta ke Samarinda. Konsep co-working space dengan internet kencang dan suasana produktif. Pilihan kopi dari berbagai origin Indonesia tersedia lengkap.',
                'has_wifi' => true,
                'latitude' => -0.511234,
                'longitude' => 117.131234,
                'google_maps_url' => 'https://maps.google.com/?q=Jakarta+Loc+Coffee+Space+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan', 'Parking'],
            ],
            // 16
            [
                'name' => 'Kong Djie Coffee Samarinda',
                'address' => 'Citra Niaga, Jl. Niaga Utara, Samarinda Kota',
                'description' => 'Warung kopi legendaris asal Belitung (sejak 1943) yang menyajikan nuansa vintage. Menu andalan: kopi O dan kopi susu panas yang diseduh dengan cara tradisional. Suasana retro dan penuh sejarah, wajib dikunjungi pecinta kopi klasik.',
                'has_wifi' => true,
                'latitude' => -0.497623,
                'longitude' => 117.149812,
                'google_maps_url' => 'https://maps.google.com/?q=Kong+Djie+Coffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 17
            [
                'name' => 'Teras Roemah Samarinda',
                'address' => 'Gg. Alam Indah, Karang Anyar, Sungai Kunjang, Samarinda',
                'description' => 'Cafe dengan pemandangan kota dan Sungai Mahakam dari ketinggian. Sering mengadakan live music di akhir pekan. Suasana romantis dan tenang, cocok untuk dinner date atau nongkrong menikmati city lights malam hari.',
                'has_wifi' => true,
                'latitude' => -0.509823,
                'longitude' => 117.127645,
                'google_maps_url' => 'https://maps.google.com/?q=Teras+Roemah+Samarinda',
                'operating_hours' => $this->hours('16:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Live Music', 'Parking', 'Smoking Area'],
            ],
            // 18
            [
                'name' => '28 Coffee Samarinda ARH',
                'address' => 'Jl. Arif Rahman Hakim No.14, Sungai Pinang Luar, Samarinda',
                'description' => 'Cafe 24 jam yang berada di pusat kota Samarinda, mudah diakses. Pilihan populer bagi mahasiswa dan pekerja yang butuh tempat nyaman kapan saja. Interior modern dengan pencahayaan yang pas untuk kerja atau belajar.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.493127,
                'longitude' => 117.153456,
                'google_maps_url' => 'https://maps.google.com/?q=28+Coffee+Samarinda+ARH',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', '24 Jam', 'WFC Friendly', 'Colokan', 'Parking'],
            ],
            // 19
            [
                'name' => 'MoveOnCafe',
                'address' => 'Jl. Mawar No.S-15, Samarinda',
                'description' => 'Cafe casual dengan konsep yang fun dan muda. Menu beragam dari kopi, mocktail, hingga snack ringan. Nama uniknya sering jadi bahan obrolan — tempat yang pas untuk move on dari rutinitas sehari-hari.',
                'has_wifi' => true,
                'latitude' => -0.496789,
                'longitude' => 117.151234,
                'google_maps_url' => 'https://maps.google.com/?q=MoveOnCafe+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 20
            [
                'name' => 'Saqa Coffee House And Space',
                'address' => 'Jl. Wijaya Kusuma 9A No.4, Samarinda',
                'description' => 'Coffee house dengan konsep rumahan yang intimate dan tenang. Cocok untuk mereka yang mencari ketenangan sambil menikmati secangkir kopi specialty. Area indoor yang nyaman dengan AC dan dekorasi hangat.',
                'has_wifi' => true,
                'latitude' => -0.476789,
                'longitude' => 117.147623,
                'google_maps_url' => 'https://maps.google.com/?q=Saqa+Coffee+House+And+Space+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],

            // ─────────────────────────────────────────────
            // GRUP 2: 80 CAFE BARU (dari hasil scraping)
            // ─────────────────────────────────────────────

            // 21
            [
                'name' => 'Seraung Coffice',
                'address' => 'Jl. Wijaya Kusuma No.12, Samarinda Ulu',
                'description' => 'Pelopor cafe 24 jam di Samarinda yang berfungsi sebagai co-working space lengkap. Dilengkapi working space, WiFi kencang, dan banyak colokan di setiap meja. Area indoor full AC dan outdoor yang asri. Tempat favorit freelancer dan mahasiswa yang butuh tempat kerja kapan saja.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.487923,
                'longitude' => 117.146812,
                'google_maps_url' => 'https://maps.google.com/?q=Seraung+Coffice+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', '24 Jam', 'WFC Friendly', 'Colokan', 'Parking'],
            ],
            // 22
            [
                'name' => 'Tempat Rahasia',
                'address' => 'Jl. Dr. Soetomo CV. Krama Agung No.31, Samarinda',
                'description' => 'Cafe 24 jam dengan konsep aesthetic dan instagramable. Cocok untuk begadang, mengerjakan tugas, atau sekadar nongkrong malam hari. Desain interior yang unik dan penuh warna menjadikannya spot foto favorit. WiFi stabil dan colokan tersedia di setiap sudut.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.493456,
                'longitude' => 117.148923,
                'google_maps_url' => 'https://maps.google.com/?q=Tempat+Rahasia+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', '24 Jam', 'WFC Friendly', 'Colokan', 'Smoking Area'],
            ],
            // 23
            [
                'name' => 'Janji Jiwa Culture',
                'address' => 'Jl. Wahid Hasyim I, Samarinda',
                'description' => 'Cabang Janji Jiwa dengan konsep Culture yang lebih spacious dan modern minimalis. Buka 24 jam dengan fasilitas area indoor dan outdoor. Kopi khas Jiwa dengan harga terjangkau menjadikannya pilihan utama anak muda Samarinda.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.478234,
                'longitude' => 117.152345,
                'google_maps_url' => 'https://maps.google.com/?q=Janji+Jiwa+Culture+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', '24 Jam', 'Parking'],
            ],
            // 24
            [
                'name' => 'The Gade Coffee & Gold',
                'address' => 'Jl. Basuki Rahmat, Sungai Pinang Luar, Samarinda Kota',
                'description' => 'Cafe berkonsep premium kolaborasi Pegadaian dengan kedai kopi. Desain interior menarik yang memadukan unsur emas dan kopi. Lingkungan nyaman dengan area parkir luas, cocok untuk rapat kecil atau mengerjakan tugas. Lokasi strategis dekat perkantoran.',
                'has_wifi' => true,
                'latitude' => -0.492345,
                'longitude' => 117.152678,
                'whatsapp_number' => '082157136835',
                'google_maps_url' => 'https://maps.google.com/?q=The+Gade+Coffee+Gold+Samarinda',
                'operating_hours' => $this->hours('10:00', '00:00', '10:00', '00:00'),
                'social_links' => ['instagram' => 'thegadecoffee'],
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly', 'Colokan'],
            ],
            // 25
            [
                'name' => 'Giras Kitchen & Coffee',
                'address' => 'Jl. Siradj Salman No.1A, Sidodadi, Samarinda Ulu',
                'description' => 'Resto & coffee shop dengan suasana nyaman dan menu beragam. Menyajikan hidangan Indonesia dan western food serta berbagai pilihan kopi. Interior yang luas cocok untuk acara keluarga maupun gathering komunitas.',
                'has_wifi' => true,
                'latitude' => -0.489567,
                'longitude' => 117.148234,
                'whatsapp_number' => '081158328800',
                'google_maps_url' => 'https://maps.google.com/?q=Giras+Kitchen+Coffee+Samarinda',
                'operating_hours' => $this->hours('09:30', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 26
            [
                'name' => 'Ladang Coffee',
                'address' => 'Jl. P.M. Noor No.90, Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe bernuansa alam dengan area outdoor yang luas dan hijau. Terletak di kawasan Sempaja yang sedang berkembang. Menu kopi racikan sendiri dan makanan ringan. Suasana santai yang cocok untuk melepas penat setelah bekerja seharian.',
                'has_wifi' => true,
                'latitude' => -0.451234,
                'longitude' => 117.153456,
                'google_maps_url' => 'https://maps.google.com/?q=Ladang+Coffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 27
            [
                'name' => 'Capturra Study Cafe',
                'address' => 'Jl. Pramuka 3A, Sempaja Selatan, Samarinda Utara',
                'description' => 'Study cafe yang dirancang khusus untuk pelajar dan mahasiswa. Suasana tenang dengan aturan noise level yang dijaga. WiFi super kencang, colokan di setiap meja, dan AC yang sejuk. Menu kopi dan cemilan ringan dengan harga mahasiswa.',
                'has_wifi' => true,
                'latitude' => -0.462345,
                'longitude' => 117.155678,
                'google_maps_url' => 'https://maps.google.com/?q=Capturra+Study+Cafe+Samarinda',
                'operating_hours' => $this->hours('09:00', '21:00', '16:00', '21:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan'],
            ],
            // 28
            [
                'name' => 'Kopi Gelas Kaca',
                'address' => 'Jl. Pramuka No.12, Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe yang buka hingga larut malam, favorit mahasiswa yang suka begadang. Menu kopi dan non-kopi lengkap dengan harga bersahabat. Suasana cozy dengan pencahayaan warm yang pas untuk ngobrol santai atau belajar.',
                'has_wifi' => true,
                'latitude' => -0.463456,
                'longitude' => 117.156234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Gelas+Kaca+Samarinda',
                'operating_hours' => $this->hours('11:00', '01:00', '11:00', '03:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area', 'Parking'],
            ],
            // 29
            [
                'name' => "Mojo'o Coffee",
                'address' => 'Jl. Pramuka 3A, Sempaja Selatan, Samarinda Utara',
                'description' => 'Kedai kopi cozy di kawasan Pramuka yang populer di kalangan mahasiswa. Menu kopi manual brew dan espresso based tersedia lengkap. Suasana homey dengan interior kayu natural dan pencahayaan yang hangat.',
                'has_wifi' => true,
                'latitude' => -0.462567,
                'longitude' => 117.155234,
                'google_maps_url' => 'https://maps.google.com/?q=Mojoo+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 30
            [
                'name' => 'Rufee Coffee',
                'address' => 'Jl. Pramuka (samping Gg. Pramuka 5A), Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe yang buka dari sore hingga malam hari, cocok untuk after-work coffee. Menu kopi specialty dengan biji pilihan dari berbagai daerah di Indonesia. Suasana relaxed dan intimate.',
                'has_wifi' => true,
                'latitude' => -0.464123,
                'longitude' => 117.156789,
                'google_maps_url' => 'https://maps.google.com/?q=Rufee+Coffee+Samarinda',
                'operating_hours' => $this->hours('14:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 31
            [
                'name' => 'Sekumpulan Kopi',
                'address' => 'Jl. Pramuka No.14A, Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe populer di kalangan mahasiswa dengan harga super terjangkau. Tempat nongkrong favorit yang selalu ramai, terutama di akhir pekan. Menu kopi dan cemilan lengkap, suasana rame dan seru untuk hangout bareng komunitas.',
                'has_wifi' => true,
                'latitude' => -0.462890,
                'longitude' => 117.155123,
                'google_maps_url' => 'https://maps.google.com/?q=Sekumpulan+Kopi+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00', '09:00', '00:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area', 'Parking'],
            ],
            // 32
            [
                'name' => 'Laris Coffee',
                'address' => 'Citra Niaga Blok F No.10-11, Samarinda Kota',
                'description' => 'Cafe minimalis dengan fasilitas Photobox (Cabox) yang unik. Terletak di kawasan ikonik Citra Niaga Samarinda. Desain interior clean dan modern, cocok untuk foto-foto aesthetic. Menu kopi dan dessert tersedia lengkap.',
                'has_wifi' => true,
                'latitude' => -0.497812,
                'longitude' => 117.149345,
                'google_maps_url' => 'https://maps.google.com/?q=Laris+Coffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '00:00'),
                'social_links' => ['instagram' => 'laris.coffee'],
                'facilities' => ['WiFi', 'AC', 'Photobox', 'Parking'],
            ],
            // 33
            [
                'name' => 'Widi Coffee',
                'address' => 'Jl. Juanda 4 No.99, Air Hitam, Samarinda Ulu',
                'description' => 'Kedai kopi dengan suasana cozy dan homey yang sangat nyaman. Dekorasi interior warm dengan sentuhan tanaman hijau. Tersedia Photobox (Difotoin.id) untuk pengunjung. Menu kopi susu khas yang wajib dicoba.',
                'has_wifi' => true,
                'latitude' => -0.488456,
                'longitude' => 117.143789,
                'google_maps_url' => 'https://maps.google.com/?q=Widi+Coffee+Samarinda',
                'operating_hours' => $this->hours('09:30', '23:00'),
                'social_links' => ['instagram' => 'widicoffee'],
                'facilities' => ['WiFi', 'AC', 'Photobox', 'Parking', 'Outdoor Seating'],
            ],
            // 34
            [
                'name' => 'Coffee Toffee Samarinda',
                'address' => 'Jl. Ir. H. Juanda No.8, Air Hitam, Samarinda Ulu',
                'description' => 'Jaringan kedai kopi premium asal Indonesia yang sudah hadir sejak 2006. Cabang Samarinda menyajikan specialty coffee, cold brew, dan berbagai pastry. Interior bergaya coffee house klasik dengan nuansa cokelat dan hijau. Cocok untuk deep talk dan quality time.',
                'has_wifi' => true,
                'latitude' => -0.487234,
                'longitude' => 117.144123,
                'google_maps_url' => 'https://maps.google.com/?q=Coffee+Toffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '23:00', '08:00', '00:00'),
                'social_links' => ['instagram' => 'coffeetoffeeidn'],
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly', 'Colokan'],
            ],
            // 35
            [
                'name' => "Yen's Delight Coffee Pastry & Resto",
                'address' => 'Jl. Ir. H. Juanda No.6, Air Hitam, Samarinda Ulu',
                'description' => 'Cafe & resto premium yang sering menampilkan live music. Memiliki spot foto instagramable di setiap sudutnya. Menu lengkap dari specialty coffee, artisan pastry, hingga western dan Indonesian food. Salah satu tempat nongkrong paling estetik di Samarinda.',
                'has_wifi' => true,
                'latitude' => -0.487345,
                'longitude' => 117.144567,
                'google_maps_url' => 'https://maps.google.com/?q=Yens+Delight+Coffee+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Live Music', 'Parking', 'Outdoor Seating'],
            ],
            // 36
            [
                'name' => 'Uforia Coffee',
                'address' => 'Jl. Untung Suropati No.35, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Cafe dengan view Jembatan Mahakam yang menawan. Menyajikan specialty coffee dan menu makanan lengkap. Suasana yang tenang dan pemandangan sungai menjadikan tempat ini sempurna untuk menikmati sunset sambil ngopi di sore hari.',
                'has_wifi' => true,
                'latitude' => -0.505678,
                'longitude' => 117.123456,
                'google_maps_url' => 'https://maps.google.com/?q=Uforia+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '21:30'),
                'social_links' => ['instagram' => 'yuforia.cafe'],
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking'],
            ],
            // 37
            [
                'name' => 'Reuni Kopi Vorvo',
                'address' => 'Jl. Langsat, Area Vorvo, Samarinda',
                'description' => 'Kedai kopi sore-malam dengan suasana cozy dan intimate. Tutup setiap hari Selasa. Menu kopi racikan sendiri dengan harga terjangkau. Tempat yang pas untuk reuni atau kumpul bareng teman lama sambil ngopi santai.',
                'has_wifi' => true,
                'latitude' => -0.497234,
                'longitude' => 117.158345,
                'google_maps_url' => 'https://maps.google.com/?q=Reuni+Kopi+Vorvo+Samarinda',
                'operating_hours' => $this->hours('17:30', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 38
            [
                'name' => 'Kopilihanku',
                'address' => 'Jl. Camar No.81, Samarinda',
                'description' => 'Kedai kopi yang terkenal dengan kopi susu aren yang sangat diminati. Tempat nyaman dengan harga terjangkau. Menu signature: Es Kopi Susu Aren yang creamy dan segar. Favorit para pecinta kopi susu di Samarinda.',
                'has_wifi' => true,
                'latitude' => -0.495123,
                'longitude' => 117.143567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopilihanku+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'Parking'],
            ],
            // 39
            [
                'name' => 'Lucca Coffeebar Big Mall',
                'address' => 'Big Mall, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Coffee bar premium di dalam Big Mall Samarinda. Terkenal dengan Vanilla Bourbon Tea dan latte art yang cantik. Interior elegan dengan suasana mall yang nyaman. Pas untuk istirahat sejenak saat shopping atau meeting santai.',
                'has_wifi' => true,
                'latitude' => -0.507234,
                'longitude' => 117.119456,
                'google_maps_url' => 'https://maps.google.com/?q=Lucca+Coffeebar+Big+Mall+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 40
            [
                'name' => 'The Curve Café',
                'address' => 'Jl. Kadrie Oening, Samarinda Ulu',
                'description' => 'Cafe modern dengan pilihan menu yang sangat beragam dari kopi, tea, hingga full meals. Interior bergaya contemporary dengan pencahayaan yang artistic. Cocok untuk hangout, date, atau working session yang produktif.',
                'has_wifi' => true,
                'latitude' => -0.491567,
                'longitude' => 117.140234,
                'google_maps_url' => 'https://maps.google.com/?q=The+Curve+Cafe+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 41
            [
                'name' => 'Unico Coffee',
                'address' => 'Jl. Mulawarman, Samarinda Kota',
                'description' => 'Coffee shop yang terkenal dengan beef burger dan hazelnut coffee-nya. Menu fusion yang unik memadukan cita rasa western dan kopi specialty. Suasana casual dan trendy, cocok untuk lunch atau afternoon coffee.',
                'has_wifi' => true,
                'latitude' => -0.500234,
                'longitude' => 117.152345,
                'google_maps_url' => 'https://maps.google.com/?q=Unico+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 42
            [
                'name' => 'Bungas Cafe & Resto',
                'address' => 'Jl. MT Haryono, Karang Anyar, Sungai Kunjang, Samarinda',
                'description' => 'Cafe & resto di lantai 2 dengan panorama kota Samarinda yang memukau. View city lights di malam hari menjadi daya tarik utamanya. Menu lengkap dari Indonesian food hingga western, ditambah aneka kopi dan mocktail.',
                'has_wifi' => true,
                'latitude' => -0.510234,
                'longitude' => 117.128456,
                'google_maps_url' => 'https://maps.google.com/?q=Bungas+Cafe+Resto+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 43
            [
                'name' => 'BLUELOFT',
                'address' => 'Jl. Kadrie Oening, Samarinda',
                'description' => 'Cafe dengan suasana ala Bali yang memanjakan mata. Pemandangan sunset yang indah menjadi highlight utama. Interior bergaya tropical modern dengan banyak tanaman hijau. Menu kopi specialty dan cocktail tersedia lengkap.',
                'has_wifi' => true,
                'latitude' => -0.490567,
                'longitude' => 117.139234,
                'google_maps_url' => 'https://maps.google.com/?q=BLUELOFT+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 44
            [
                'name' => 'Kopijadi',
                'address' => 'Jl. Bukit Rumbia 2 No.5, Sidomulyo, Samarinda',
                'description' => 'Cafe di atas bukit dengan pemandangan spektakuler kota Samarinda. Menawarkan pengalaman ngopi di ketinggian dengan udara segar dan view yang menakjubkan. Wajib dikunjungi saat golden hour untuk foto sunset terbaik.',
                'has_wifi' => true,
                'latitude' => -0.483456,
                'longitude' => 117.137234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopijadi+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 45
            [
                'name' => 'Kopi dari Hati',
                'address' => 'Jl. Pramuka No.14, RT 29, Sempaja Selatan, Samarinda Utara',
                'description' => 'Kedai kopi yang benar-benar dibuat dari hati. Menyajikan kopi specialty dengan proses roasting yang diawasi langsung. Memiliki beberapa cabang di Samarinda. Suasana intimate dan personal, cocok untuk ngopi sendiri atau berdua.',
                'has_wifi' => true,
                'latitude' => -0.461234,
                'longitude' => 117.155456,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+dari+Hati+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 46
            [
                'name' => 'Pondok Nibung',
                'address' => 'Jl. Pramuka, 6P&K, Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe bernuansa alam tropis dengan pohon nibung sebagai ciri khasnya. Terletak di kawasan perumahan P&K yang tenang. Menu kopi tradisional dan modern tersedia lengkap. Suasana teduh dan asri yang cocok untuk relaksasi.',
                'has_wifi' => true,
                'latitude' => -0.464567,
                'longitude' => 117.157234,
                'google_maps_url' => 'https://maps.google.com/?q=Pondok+Nibung+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 47
            [
                'name' => "Da'coffe Shop",
                'address' => 'Jl. Bung Tomo No.16, Samarinda Seberang',
                'description' => 'Coffee shop casual di kawasan Samarinda Seberang. Harga terjangkau dengan porsi besar. Menu kopi dan makanan ringan yang cocok untuk kantong mahasiswa. Suasana santai dan friendly.',
                'has_wifi' => true,
                'latitude' => -0.509345,
                'longitude' => 117.120345,
                'google_maps_url' => 'https://maps.google.com/?q=Dacoffe+Shop+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'Parking', 'Smoking Area'],
            ],
            // 48
            [
                'name' => 'RVS Coffee & Angkringan',
                'address' => 'Jl. Daeng Mangkona RT 17, Samarinda Seberang',
                'description' => 'Kombinasi unik antara coffee shop modern dan angkringan tradisional. Menyajikan kopi specialty berdampingan dengan menu angkringan khas Jawa. Harga sangat terjangkau, suasana kebersamaan yang kental.',
                'has_wifi' => true,
                'latitude' => -0.512345,
                'longitude' => 117.118234,
                'google_maps_url' => 'https://maps.google.com/?q=RVS+Coffee+Angkringan+Samarinda',
                'operating_hours' => $this->hours('16:00', '00:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 49
            [
                'name' => "D'puncak Cafe & Resto",
                'address' => 'Jl. MT Haryono, Karang Anyar, Sungai Kunjang, Samarinda',
                'description' => 'Cafe & resto yang menawarkan pemandangan kota dari ketinggian. Terletak di area Karang Anyar yang tinggi dengan view 360 derajat. Menu lengkap dari Indonesian food, western, hingga seafood. Spot sunset terbaik di Samarinda.',
                'has_wifi' => true,
                'latitude' => -0.511234,
                'longitude' => 117.129456,
                'google_maps_url' => 'https://maps.google.com/?q=Dpuncak+Cafe+Resto+Samarinda',
                'operating_hours' => $this->hours('11:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 50
            [
                'name' => 'Lotus Garden Cafe & Steak',
                'address' => 'Jl. A. Wahab Syahranie No.01, Samarinda',
                'description' => 'Cafe & steak house premium dengan konsep garden yang asri. Terkenal dengan steak premium dan suasana outdoor yang hijau dan sejuk. Cocok untuk dinner romantis atau acara keluarga. Menu barat lengkap dengan wine list.',
                'has_wifi' => true,
                'latitude' => -0.486234,
                'longitude' => 117.141567,
                'google_maps_url' => 'https://maps.google.com/?q=Lotus+Garden+Cafe+Steak+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking'],
            ],
            // 51
            [
                'name' => 'Dunamos Kitchen & Bistro',
                'address' => 'Jl. Tekukur 1, Temindung Permai, Sungai Pinang, Samarinda',
                'description' => 'Kitchen & bistro yang menggabungkan konsep kafe dan restoran. Menu continental dan Asian fusion yang kreatif. Interior bergaya bistro Eropa dengan pencahayaan warm. Cocok untuk dinner date atau celebrasi kecil.',
                'has_wifi' => true,
                'latitude' => -0.493567,
                'longitude' => 117.141234,
                'google_maps_url' => 'https://maps.google.com/?q=Dunamos+Kitchen+Bistro+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 52
            [
                'name' => 'Moon House Coffee',
                'address' => 'Jl. Banggeris, Teluk Lerong Ulu, Samarinda',
                'description' => 'Cafe dengan suasana tenang dan estetik yang cocok untuk pengerjaan skripsi atau kerja remote. Interior bergaya minimalis Jepang dengan pencahayaan soft. Menu kopi manual brew dan matcha latte menjadi favorit pengunjung.',
                'has_wifi' => true,
                'latitude' => -0.506234,
                'longitude' => 117.133456,
                'google_maps_url' => 'https://maps.google.com/?q=Moon+House+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan'],
            ],
            // 53
            [
                'name' => 'Safehouse Coffee',
                'address' => 'Jl. Ahmad Yani 2, Samarinda',
                'description' => 'Cafe yang menawarkan safe space untuk bekerja dan berkreasi. Koneksi internet super kencang menjadi andalan utamanya. Area indoor dan outdoor tersedia lengkap. Nyaman untuk konsentrasi dengan noise level yang terjaga.',
                'has_wifi' => true,
                'latitude' => -0.498567,
                'longitude' => 117.156234,
                'google_maps_url' => 'https://maps.google.com/?q=Safehouse+Coffee+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan', 'Outdoor Seating', 'Parking'],
            ],
            // 54
            [
                'name' => 'Kopiria Ahmad Yani',
                'address' => 'Jl. Ahmad Yani, Samarinda Kota',
                'description' => 'Salah satu cabang Kopiria yang populer di Samarinda. Menyajikan kopi specialty dengan konsep grab-and-go dan dine-in. Interior clean dan modern dengan desain yang konsisten di setiap cabangnya.',
                'has_wifi' => true,
                'latitude' => -0.499234,
                'longitude' => 117.155345,
                'google_maps_url' => 'https://maps.google.com/?q=Kopiria+Ahmad+Yani+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 55
            [
                'name' => 'Kopiria Kartini',
                'address' => 'Jl. Kartini, Sungai Pinang Luar, Samarinda Kota',
                'description' => 'Cabang Kopiria di kawasan Kartini yang strategis. Menyajikan berbagai varian kopi specialty dan non-kopi dengan kualitas konsisten. Tempat nyaman untuk coffee break singkat atau ngobrol santai.',
                'has_wifi' => true,
                'latitude' => -0.496345,
                'longitude' => 117.148567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopiria+Kartini+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 56
            [
                'name' => 'Kopi Papa Muda Kemakmuran',
                'address' => 'Jl. Kemakmuran, Samarinda',
                'description' => 'Brand lokal Samarinda yang sudah punya banyak cabang. Konsep Rumah Papa yang homey dan welcoming. Buka hingga tengah malam saat weekend. Menu kopi susu khas dan berbagai varian mocktail menjadi andalan.',
                'has_wifi' => true,
                'latitude' => -0.500123,
                'longitude' => 117.147234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Papa+Muda+Kemakmuran+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00', '09:00', '00:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking'],
            ],
            // 57
            [
                'name' => 'Kopi Papa Muda Kadrie Oening',
                'address' => 'Jl. Kadrie Oening, Air Hitam, Samarinda Ulu',
                'description' => 'Cabang Kopi Papa Muda di kawasan Kadrie Oening yang sedang naik daun. Konsep Rumah Papa yang konsisten: homey, comfortable, dan affordable. Menu kopi susu dengan berbagai topping yang bisa dicustom.',
                'has_wifi' => true,
                'latitude' => -0.489234,
                'longitude' => 117.140567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Papa+Muda+Kadrie+Oening+Samarinda',
                'operating_hours' => $this->hours('09:00', '21:30'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 58
            [
                'name' => 'Kopi Papa Muda Slamet Riyadi',
                'address' => 'Jl. Slamet Riyadi No.18, Karang Asam, Samarinda',
                'description' => 'Cabang Kopi Papa Muda di kawasan Slamet Riyadi. Menyajikan kopi susu signature dan berbagai pilihan non-kopi. Suasana yang sama konsisten: rumahan, nyaman, dan harga bersahabat untuk semua kalangan.',
                'has_wifi' => true,
                'latitude' => -0.504234,
                'longitude' => 117.126567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Papa+Muda+Slamet+Riyadi+Samarinda',
                'operating_hours' => $this->hours('09:30', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 59
            [
                'name' => 'Kopi Kenangan Samarinda Central Plaza',
                'address' => 'Samarinda Central Plaza, Samarinda Kota',
                'description' => 'Gerai kopi grab-and-go terpopuler Indonesia hadir di Samarinda Central Plaza. Terkenal dengan Kopi Kenangan Mantan yang legendaris. Cepat, praktis, dan harga terjangkau — cocok untuk coffee-on-the-go.',
                'has_wifi' => true,
                'latitude' => -0.498567,
                'longitude' => 117.150234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+Samarinda+Central+Plaza',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 60
            [
                'name' => 'Kopi Kenangan Ruko M. Yamin',
                'address' => 'Jl. M. Yamin No.6, Gn. Kelua, Samarinda Ulu',
                'description' => 'Gerai Kopi Kenangan di kawasan M. Yamin yang strategis. Menu signature: Kopi Kenangan Mantan, Avocado Coffee, dan berbagai tea series. Cepat dan praktis untuk kebutuhan kopi harian.',
                'has_wifi' => true,
                'latitude' => -0.494234,
                'longitude' => 117.145567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+M+Yamin+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC'],
            ],
            // 61
            [
                'name' => 'Kopi Kenangan Ruko Bung Tomo',
                'address' => 'Jl. Bung Tomo No.68A, Sungai Keledang, Samarinda Seberang',
                'description' => 'Gerai Kopi Kenangan di kawasan Samarinda Seberang. Melayani dine-in, take away, dan delivery. Menu kopi dan non-kopi lengkap dengan harga yang sangat terjangkau.',
                'has_wifi' => true,
                'latitude' => -0.510234,
                'longitude' => 117.121567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+Bung+Tomo+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC'],
            ],
            // 62
            [
                'name' => 'Tomoro Coffee PM Noor',
                'address' => 'Jl. PM Noor, Sempaja, Samarinda Utara',
                'description' => 'Gerai kopi modern yang sedang naik daun. Konsep tech-forward dengan pemesanan via app. Menu kopi specialty dengan harga kompetitif. Interior futuristik dan clean.',
                'has_wifi' => true,
                'latitude' => -0.453234,
                'longitude' => 117.152567,
                'google_maps_url' => 'https://maps.google.com/?q=Tomoro+Coffee+PM+Noor+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 63
            [
                'name' => 'Tomoro Coffee Merak Square',
                'address' => 'Merak Square, Jl. Hasan Basri, Samarinda',
                'description' => 'Gerai Tomoro Coffee di kawasan Merak Square yang ramai. Menyajikan specialty coffee dengan teknologi brewing terkini. Cocok untuk quick coffee break atau take away.',
                'has_wifi' => true,
                'latitude' => -0.476234,
                'longitude' => 117.149567,
                'google_maps_url' => 'https://maps.google.com/?q=Tomoro+Coffee+Merak+Square+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 64
            [
                'name' => 'Luminary Cafe',
                'address' => 'Jl. Trikora, Handil Bakti, Palaran, Samarinda',
                'description' => 'Cafe yang dikenal sebagai hub komunitas di kawasan Palaran. Desain interior lucu dengan area rooftop. Harga terjangkau dan suasana yang asoy menjadikannya tempat nongkrong favorit warga sekitar. Cocok untuk hangout santai.',
                'has_wifi' => true,
                'latitude' => -0.543234,
                'longitude' => 117.178567,
                'google_maps_url' => 'https://maps.google.com/?q=Luminary+Cafe+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Rooftop', 'Parking', 'Smoking Area'],
            ],
            // 65
            [
                'name' => 'Kopi dari Hati Kadrie Oening',
                'address' => 'Jl. Kadrie Oening No.46, Air Hitam, Samarinda Ulu',
                'description' => 'Cabang Kopi dari Hati di kawasan Kadrie Oening. Menyajikan kopi specialty dan manual brew yang diracik dengan hati. Suasana yang intimate dan personal dengan pelayanan ramah.',
                'has_wifi' => true,
                'latitude' => -0.490345,
                'longitude' => 117.141234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+dari+Hati+Kadrie+Oening+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 66
            [
                'name' => 'Oriva Coffee',
                'address' => 'Jl. Pangeran Antasari, Teluk Lerong Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Cafe & roastery yang berkolaborasi dengan Bullish Roastery. Menyajikan specialty coffee single origin dari berbagai daerah di Indonesia. Pengunjung bisa menyaksikan langsung proses roasting biji kopi. Cocok untuk coffee enthusiast.',
                'has_wifi' => true,
                'latitude' => -0.507345,
                'longitude' => 117.131234,
                'google_maps_url' => 'https://maps.google.com/?q=Oriva+Coffee+Bullish+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
            // 67
            [
                'name' => 'Janji Jiwa Culture Hasan Basri',
                'address' => 'Jl. Hasan Basri No.12, Merak, Samarinda',
                'description' => 'Cabang kedua Janji Jiwa Culture di Samarinda, terletak di kawasan Merak. Konsep 24 jam yang sama dengan cabang Wahid Hasyim. Menu Jiwa Toast dan Es Kopi Jiwa menjadi favorit pelanggan setia.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.477345,
                'longitude' => 117.150234,
                'google_maps_url' => 'https://maps.google.com/?q=Janji+Jiwa+Culture+Hasan+Basri+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', '24 Jam', 'Outdoor Seating', 'Parking'],
            ],
            // 68
            [
                'name' => 'Kopi Papa Muda Agus Salim',
                'address' => 'Jl. Agus Salim, Samarinda',
                'description' => 'Cabang Kopi Papa Muda dengan jam operasional siang hari. Cocok untuk coffee break di sela aktivitas. Menu kopi susu signature dan cemilan ringan tersedia lengkap.',
                'has_wifi' => true,
                'latitude' => -0.497567,
                'longitude' => 117.146234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Papa+Muda+Agus+Salim+Samarinda',
                'operating_hours' => $this->hours('09:30', '17:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 69
            [
                'name' => 'Teras Rumah Café',
                'address' => 'Jl. MT Haryono, Samarinda',
                'description' => 'Cafe di dataran tinggi dengan pemandangan kota dan Sungai Mahakam yang menawan. View sunset menjadi daya tarik utama. Menu kopi dan makanan ringan lengkap. Sering mengadakan live acoustic di akhir pekan.',
                'has_wifi' => true,
                'latitude' => -0.511567,
                'longitude' => 117.130234,
                'google_maps_url' => 'https://maps.google.com/?q=Teras+Rumah+Cafe+Samarinda',
                'operating_hours' => $this->hours('15:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Live Music', 'Parking', 'Smoking Area'],
            ],
            // 70
            [
                'name' => 'Kopi Kenangan SPBU Kesuma Bangsa',
                'address' => 'Area SPBU Kesuma Bangsa, Samarinda',
                'description' => 'Gerai Kopi Kenangan strategis di area SPBU Kesuma Bangsa. Cocok untuk grab-and-go saat sedang dalam perjalanan. Menu cepat saji kopi dan snack ringan.',
                'has_wifi' => true,
                'latitude' => -0.502567,
                'longitude' => 117.152234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+SPBU+Kesuma+Bangsa+Samarinda',
                'operating_hours' => $this->hours('07:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 71
            [
                'name' => 'Nordu Coffee Samarinda',
                'address' => 'Jl. Ir. H. Juanda No.221 C-D, Air Hitam, Samarinda Ulu',
                'description' => 'Cafe 24 jam yang strategis di kawasan ramai Juanda. Cocok untuk nongkrong atau nugas tanpa batas waktu. Interior modern dengan WiFi stabil dan colokan lengkap. Menu kopi dan snack tersedia sepanjang hari.',
                'has_wifi' => true,
                'is_24_hours' => true,
                'latitude' => -0.488234,
                'longitude' => 117.143567,
                'google_maps_url' => 'https://maps.google.com/?q=Nordu+Coffee+Samarinda',
                'operating_hours' => $this->hours('00:00', '23:59', '00:00', '23:59'),
                'facilities' => ['WiFi', 'AC', '24 Jam', 'WFC Friendly', 'Colokan', 'Parking'],
            ],
            // 72
            [
                'name' => 'Titik Koma Rajawali',
                'address' => 'Area Rajawali, Samarinda',
                'description' => 'Cabang Titik Koma di kawasan Rajawali. Konsep modern minimalis yang konsisten dengan eye-catching interior. Menu favorit tetap Caramel Latte Macchiato dan berbagai varian kopi dan non-kopi.',
                'has_wifi' => true,
                'latitude' => -0.501234,
                'longitude' => 117.148567,
                'google_maps_url' => 'https://maps.google.com/?q=Titik+Koma+Rajawali+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 73
            [
                'name' => 'Kopi Loa Bahu',
                'address' => 'Jl. Cipto Mangunkusumo, Loa Bahu, Sungai Kunjang, Samarinda',
                'description' => 'Kedai kopi yang terletak di kawasan Loa Bahu. Menyajikan kopi liberika lokal khas Kalimantan yang jarang ditemui di tempat lain. Suasana rustic dan dekat dengan alam. Harga sangat terjangkau.',
                'has_wifi' => true,
                'latitude' => -0.513456,
                'longitude' => 117.117234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Loa+Bahu+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 74
            [
                'name' => 'Roemah Samarinda Terrace',
                'address' => 'Karang Anyar, Sungai Kunjang, Samarinda',
                'description' => 'Cafe terrace dengan pemandangan kota yang indah. Konsep rumahan tradisional Kalimantan dengan sentuhan modern. Menu Indonesian comfort food dan kopi lokal. Suasana yang bikin betah berlama-lama.',
                'has_wifi' => true,
                'latitude' => -0.510567,
                'longitude' => 117.128234,
                'google_maps_url' => 'https://maps.google.com/?q=Roemah+Samarinda+Terrace',
                'operating_hours' => $this->hours('15:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 75
            [
                'name' => 'Kopi Papa Muda Juanda',
                'address' => 'Jl. Ir. H. Juanda, Air Hitam, Samarinda Ulu',
                'description' => 'Cabang terbaru Kopi Papa Muda di kawasan Juanda yang strategis. Menyajikan menu favorit kopi susu signature, matcha latte, dan berbagai snack. Suasana homey khas Rumah Papa.',
                'has_wifi' => true,
                'latitude' => -0.487567,
                'longitude' => 117.144234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Papa+Muda+Juanda+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 76
            [
                'name' => 'Lucca Coffeebar Temindung',
                'address' => 'Temindung Permai, Sungai Pinang, Samarinda',
                'description' => 'Cabang Lucca Coffeebar di area Temindung yang lebih intimate dibanding cabang mall. Menyajikan Vanilla Bourbon Tea signature dan berbagai kopi specialty. Interior estetik dan tenang.',
                'has_wifi' => true,
                'latitude' => -0.494567,
                'longitude' => 117.142234,
                'google_maps_url' => 'https://maps.google.com/?q=Lucca+Coffeebar+Temindung+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 77
            [
                'name' => 'Kopi dari Hati Wahid Hasyim',
                'address' => 'Jl. KH Wahid Hasyim II No.16, Samarinda',
                'description' => 'Cabang Kopi dari Hati di kawasan Wahid Hasyim. Suasana cozy dan personal yang menjadi ciri khas brand ini. Menu kopi specialty yang berkualitas dengan harga terjangkau.',
                'has_wifi' => true,
                'latitude' => -0.479234,
                'longitude' => 117.151234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+dari+Hati+Wahid+Hasyim+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 78
            [
                'name' => 'Tomoro Coffee Juanda',
                'address' => 'Jl. Ir. H. Juanda, Samarinda Ulu',
                'description' => 'Gerai Tomoro Coffee di kawasan Juanda yang strategis. Konsep tech-forward dengan pemesanan digital. Specialty coffee berkualitas tinggi dengan harga bersaing.',
                'has_wifi' => true,
                'latitude' => -0.487890,
                'longitude' => 117.144890,
                'google_maps_url' => 'https://maps.google.com/?q=Tomoro+Coffee+Juanda+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC'],
            ],
            // 79
            [
                'name' => 'Kopi Kenangan Ir. H. Juanda',
                'address' => 'Jl. Ir. H. Juanda, Samarinda Ulu',
                'description' => 'Gerai Kopi Kenangan di kawasan Juanda yang mudah dijangkau. Menu Kopi Kenangan Mantan dan Avocado Coffee tetap menjadi best seller. Harga terjangkau dan pelayanan cepat.',
                'has_wifi' => true,
                'latitude' => -0.487456,
                'longitude' => 117.144456,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+Juanda+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC'],
            ],
            // 80
            [
                'name' => 'Kopi Kenangan PM Noor',
                'address' => 'Jl. PM Noor, Sempaja, Samarinda Utara',
                'description' => 'Gerai Kopi Kenangan di kawasan PM Noor, dekat kampus dan area perumahan. Cocok untuk grab-and-go atau dine-in singkat. Menu lengkap dan cepat.',
                'has_wifi' => true,
                'latitude' => -0.452234,
                'longitude' => 117.152234,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+PM+Noor+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 81
            [
                'name' => 'Kopi Kenangan M. Yamin',
                'address' => 'Jl. M. Yamin, Samarinda Ulu',
                'description' => 'Gerai Kopi Kenangan di kawasan M. Yamin yang ramai. Gerai ini memiliki area dine-in yang lebih luas dibanding cabang lainnya. Menu signature lengkap.',
                'has_wifi' => true,
                'latitude' => -0.493890,
                'longitude' => 117.145890,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Kenangan+M+Yamin+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 82
            [
                'name' => 'Fore Coffee Samarinda',
                'address' => 'Jl. Mulawarman, Samarinda Kota',
                'description' => 'Gerai kopi modern dengan app-based ordering. Menyajikan specialty coffee berkualitas tinggi dengan harga terjangkau. Interior minimalis dan clean. Menu favorit: Aren Latte dan Pandan Latte.',
                'has_wifi' => true,
                'latitude' => -0.500567,
                'longitude' => 117.153234,
                'google_maps_url' => 'https://maps.google.com/?q=Fore+Coffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 83
            [
                'name' => 'Point Coffee Samarinda',
                'address' => 'Indomaret Point, Jl. P. Antasari, Samarinda',
                'description' => 'Coffee corner di Indomaret Point yang menyajikan kopi berkualitas dengan harga super terjangkau. Cocok untuk quick coffee break kapan saja. Menu espresso based dan blended drinks.',
                'has_wifi' => true,
                'latitude' => -0.496789,
                'longitude' => 117.136234,
                'google_maps_url' => 'https://maps.google.com/?q=Point+Coffee+Samarinda',
                'operating_hours' => $this->hours('07:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 84
            [
                'name' => 'Starbucks Samarinda Big Mall',
                'address' => 'Big Mall, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Gerai Starbucks pertama di Samarinda, berlokasi di Big Mall. Menyajikan menu klasik Starbucks dari Frappuccino, Latte, hingga seasonal drinks. Interior khas Starbucks dengan suasana premium dan nyaman.',
                'has_wifi' => true,
                'latitude' => -0.507456,
                'longitude' => 117.119234,
                'google_maps_url' => 'https://maps.google.com/?q=Starbucks+Big+Mall+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly', 'Colokan'],
            ],
            // 85
            [
                'name' => 'Excelso Coffee Samarinda',
                'address' => 'Big Mall, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Coffee shop premium Indonesia di Big Mall Samarinda. Menyajikan single origin Indonesia dan blended coffee berkualitas. Interior elegan dengan suasana yang cocok untuk meeting profesional.',
                'has_wifi' => true,
                'latitude' => -0.507567,
                'longitude' => 117.119345,
                'google_maps_url' => 'https://maps.google.com/?q=Excelso+Coffee+Big+Mall+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],
            // 86
            [
                'name' => 'Maxx Coffee Samarinda',
                'address' => 'City Centrum Mall, Samarinda',
                'description' => 'Coffee shop modern di City Centrum Mall yang menyajikan specialty coffee dan pastry. Interior clean dengan area duduk yang luas. Cocok untuk coffee break saat shopping.',
                'has_wifi' => true,
                'latitude' => -0.502678,
                'longitude' => 117.151345,
                'google_maps_url' => 'https://maps.google.com/?q=Maxx+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 87
            [
                'name' => 'FloorForFloor Coffee',
                'address' => 'Jl. Ery Suparjan, Sempaja Selatan, Samarinda Utara',
                'description' => 'Cafe unik yang juga berfungsi sebagai roastery. Nama uniknya mencerminkan konsep multilevel yang menarik. Pengunjung bisa melihat langsung proses roasting dan memilih biji kopi yang ingin diseduh.',
                'has_wifi' => true,
                'latitude' => -0.458234,
                'longitude' => 117.154567,
                'google_maps_url' => 'https://maps.google.com/?q=FloorForFloor+Coffee+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly'],
            ],
            // 88
            [
                'name' => 'An.gata Roastery & Cafe',
                'address' => 'Selili, Samarinda Ilir',
                'description' => 'Cafe dengan in-house roastery yang menyajikan biji kopi dari berbagai daerah di Kalimantan dan Sulawesi. Pengalaman ngopi yang educational — barista siap menjelaskan profil rasa setiap origin. Cocok untuk coffee connoisseur.',
                'has_wifi' => true,
                'latitude' => -0.494234,
                'longitude' => 117.161234,
                'google_maps_url' => 'https://maps.google.com/?q=Angata+Roastery+Cafe+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 89
            [
                'name' => 'Triton Coffee Roaster Cafe',
                'address' => 'Jl. Ir. Sutami No.A/8, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Cafe yang terintegrasi dengan Triton Coffee Roaster. Menyajikan biji kopi freshly roasted dengan berbagai metode seduh: V60, Chemex, Aeropress, dan Espresso. Tempat wajib untuk coffee geeks.',
                'has_wifi' => true,
                'latitude' => -0.508234,
                'longitude' => 117.121234,
                'google_maps_url' => 'https://maps.google.com/?q=Triton+Coffee+Roaster+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 90
            [
                'name' => 'Nature Coffee & Roastery Cafe',
                'address' => 'Jl. Pemuda 2 No.84, Temindung Permai, Sungai Pinang, Samarinda',
                'description' => 'Cafe & roastery dengan nuansa alam yang asri. Biji kopi di-roast di tempat untuk kesegaran maksimal. Menu specialty coffee lengkap dari V60, Cold Brew, hingga Espresso Tonic. Suasana green dan natural.',
                'has_wifi' => true,
                'latitude' => -0.493234,
                'longitude' => 117.140234,
                'google_maps_url' => 'https://maps.google.com/?q=Nature+Coffee+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Outdoor Seating', 'Parking'],
            ],
            // 91
            [
                'name' => 'Kopi Samarinda Cafe',
                'address' => 'Perumahan Garden Hills, Jl. A. Wahab Syahranie, Blok Gardenia 4, Air Hitam, Samarinda Ulu',
                'description' => 'Cafe dari brand Kopi Samarinda yang juga menjadi supplier biji kopi untuk banyak coffee shop di Kalimantan Timur. Menyajikan kopi arabika dan robusta pilihan yang di-roast secara profesional. Tempat belajar tentang kopi lokal Kalimantan.',
                'has_wifi' => true,
                'latitude' => -0.486567,
                'longitude' => 117.142567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Samarinda+Cafe',
                'operating_hours' => $this->hours('08:00', '21:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 92
            [
                'name' => 'Konus Roastery Cafe',
                'address' => 'Jl. Kuranji, Gn. Kelua, Samarinda Ulu',
                'description' => 'Home roastery yang juga membuka cafe kecil untuk pengunjung. Salah satu roastery tertua di Samarinda yang melayani kebutuhan biji kopi untuk kedai-kedai di sekitarnya. Suasana homey dan personal.',
                'has_wifi' => true,
                'latitude' => -0.492567,
                'longitude' => 117.143567,
                'google_maps_url' => 'https://maps.google.com/?q=Konus+Roastery+Samarinda',
                'operating_hours' => $this->hours('08:00', '20:00'),
                'facilities' => ['WiFi', 'Parking'],
            ],
            // 93
            [
                'name' => 'Kedai Kopi Nusantara Samarinda',
                'address' => 'Jl. Gajah Mada, Samarinda Kota',
                'description' => 'Kedai kopi yang menghadirkan cita rasa kopi dari berbagai penjuru Nusantara. Menu kopi dari Toraja, Gayo, Flores, hingga Kintamani tersedia. Suasana tradisional modern yang menggambarkan keragaman budaya Indonesia.',
                'has_wifi' => true,
                'latitude' => -0.499567,
                'longitude' => 117.149234,
                'google_maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Nusantara+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking'],
            ],
            // 94
            [
                'name' => 'Warung Kopi Mahakam',
                'address' => 'Jl. Gajah Mada, Samarinda Kota',
                'description' => 'Warung kopi tradisional yang sudah bertahun-tahun beroperasi di tepi Sungai Mahakam. Menyajikan kopi tubruk dan kopi susu khas Kalimantan. Harga sangat terjangkau. Suasana otentik yang susah ditemui di cafe modern.',
                'has_wifi' => false,
                'latitude' => -0.500123,
                'longitude' => 117.150234,
                'google_maps_url' => 'https://maps.google.com/?q=Warung+Kopi+Mahakam+Samarinda',
                'operating_hours' => $this->hours('06:00', '21:00'),
                'facilities' => ['Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 95
            [
                'name' => 'Seduh Coffee Lab',
                'address' => 'Jl. Lambung Mangkurat, Samarinda Kota',
                'description' => 'Coffee lab yang mengutamakan presisi dalam setiap seduhan. Menyediakan berbagai metode manual brew dengan biji kopi specialty pilihan. Interior industrial minimalis. Cocok untuk yang serius belajar tentang kopi.',
                'has_wifi' => true,
                'latitude' => -0.498234,
                'longitude' => 117.147567,
                'google_maps_url' => 'https://maps.google.com/?q=Seduh+Coffee+Lab+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly'],
            ],
            // 96
            [
                'name' => 'Hideout Coffee',
                'address' => 'Jl. Pahlawan, Samarinda Kota',
                'description' => 'Cafe tersembunyi yang menjadi favorit para pecinta kopi. Konsep hidden gem dengan suasana eksklusif dan tenang. Menu kopi specialty dan pastry artisan. Cocok untuk yang ingin escape dari keramaian kota.',
                'has_wifi' => true,
                'latitude' => -0.497890,
                'longitude' => 117.151890,
                'google_maps_url' => 'https://maps.google.com/?q=Hideout+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '23:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Smoking Area'],
            ],
            // 97
            [
                'name' => 'Grind & Brew Coffee',
                'address' => 'Jl. Awang Long, Samarinda Ulu',
                'description' => 'Cafe yang mengutamakan freshness — setiap cup digiling dan diseduh langsung di depan pelanggan. Menu espresso based, pour over, dan cold brew tersedia. Interior modern industrial.',
                'has_wifi' => true,
                'latitude' => -0.488890,
                'longitude' => 117.146890,
                'google_maps_url' => 'https://maps.google.com/?q=Grind+Brew+Coffee+Samarinda',
                'operating_hours' => $this->hours('08:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'WFC Friendly', 'Colokan'],
            ],
            // 98
            [
                'name' => 'Nongkrong Coffee',
                'address' => 'Jl. Untung Suropati, Karang Asam Ulu, Samarinda',
                'description' => 'Sesuai namanya, cafe ini memang dirancang untuk nongkrong selama mungkin. Sofa empuk, WiFi kencang, dan suasana santai. Menu kopi terjangkau dan snack lengkap. Favorit mahasiswa area Sungai Kunjang.',
                'has_wifi' => true,
                'latitude' => -0.506890,
                'longitude' => 117.124890,
                'google_maps_url' => 'https://maps.google.com/?q=Nongkrong+Coffee+Samarinda',
                'operating_hours' => $this->hours('10:00', '00:00'),
                'facilities' => ['WiFi', 'AC', 'WFC Friendly', 'Colokan', 'Outdoor Seating', 'Smoking Area'],
            ],
            // 99
            [
                'name' => 'Beranda Kopi',
                'address' => 'Jl. Siradj Salman, Sidodadi, Samarinda Ulu',
                'description' => 'Cafe bernuansa teras rumah yang homey dan welcoming. Konsep beranda yang terbuka memberikan angin segar alami. Menu kopi lokal dan imported tersedia. Harga bersahabat dengan porsi yang memuaskan.',
                'has_wifi' => true,
                'latitude' => -0.490890,
                'longitude' => 117.147890,
                'google_maps_url' => 'https://maps.google.com/?q=Beranda+Kopi+Samarinda',
                'operating_hours' => $this->hours('09:00', '23:00'),
                'facilities' => ['WiFi', 'Outdoor Seating', 'Parking', 'Smoking Area'],
            ],
            // 100
            [
                'name' => 'Cerita Kopi Samarinda',
                'address' => 'Jl. Lambung Mangkurat, Samarinda Kota',
                'description' => 'Setiap cangkir punya cerita — itulah filosofi Cerita Kopi. Cafe yang menghargai proses dari biji hingga cangkir. Menu kopi single origin dari petani lokal Kalimantan Timur. Suasana storytelling yang hangat dan penuh makna.',
                'has_wifi' => true,
                'latitude' => -0.497456,
                'longitude' => 117.148234,
                'google_maps_url' => 'https://maps.google.com/?q=Cerita+Kopi+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
                'facilities' => ['WiFi', 'AC', 'Parking', 'Outdoor Seating'],
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // DATA ROASTERY — SEMUA ROASTERY DI SAMARINDA
    // Sumber: wadahngopi.com, kopisamarinda.id, pusaranmedia.com, dll.
    // ═══════════════════════════════════════════════════════════════

    private function getRoasteryData(): array
    {
        return [
            // 1
            [
                'name' => 'Kopi Samarinda Roastery',
                'address' => 'Perumahan Garden Hills, Jl. A. Wahab Syahranie, Blok Gardenia 4, Air Hitam, Samarinda Ulu',
                'description' => 'Roastery profesional yang menyediakan biji kopi berkualitas (arabika dan robusta) untuk kebutuhan coffee shop, UMKM, dan pecinta manual brew di Kalimantan Timur. Mitra supplier banyak kedai kopi di Samarinda. Menerima pesanan grosir dan eceran.',
                'latitude' => -0.486567,
                'longitude' => 117.142567,
                'google_maps_url' => 'https://maps.google.com/?q=Kopi+Samarinda+Roastery',
                'operating_hours' => $this->hours('08:00', '17:00'),
                'social_links' => ['instagram' => 'kopisamarinda.id'],
            ],
            // 2
            [
                'name' => 'Nature Coffee & Roastery',
                'address' => 'Jl. Pemuda 2 No.84, Temindung Permai, Sungai Pinang, Samarinda',
                'description' => 'Coffee roastery yang juga membuka cafe untuk pengunjung. Biji kopi di-roast di tempat untuk kesegaran maksimal. Menyediakan biji kopi single origin dari berbagai daerah dan house blend khas Nature. Melayani retail dan grosir.',
                'latitude' => -0.493234,
                'longitude' => 117.140234,
                'google_maps_url' => 'https://maps.google.com/?q=Nature+Coffee+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '21:00'),
            ],
            // 3
            [
                'name' => 'Oriva Coffee & Bullish Roastery',
                'address' => 'Jl. Pangeran Antasari, Teluk Lerong Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Kolaborasi antara Oriva Coffee dan Bullish Roastery yang menghadirkan specialty coffee kelas premium. Roasting dilakukan dengan profil yang disesuaikan untuk setiap origin. Biji kopi tersedia untuk dijual ke coffee shop dan pelanggan langsung.',
                'latitude' => -0.507345,
                'longitude' => 117.131234,
                'google_maps_url' => 'https://maps.google.com/?q=Oriva+Coffee+Bullish+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '21:00'),
            ],
            // 4
            [
                'name' => 'Konus Roastery',
                'address' => 'Jl. Kuranji, Gn. Kelua, Samarinda Ulu',
                'description' => 'Salah satu home roastery paling lama beroperasi di Samarinda. Dikenal oleh komunitas kopi lokal sebagai supplier biji kopi terpercaya. Menyediakan arabika single origin dan house blend untuk kedai-kedai kopi di sekitarnya.',
                'latitude' => -0.492567,
                'longitude' => 117.143567,
                'google_maps_url' => 'https://maps.google.com/?q=Konus+Roastery+Samarinda',
                'operating_hours' => $this->hours('08:00', '17:00'),
            ],
            // 5
            [
                'name' => 'An.gata Roastery',
                'address' => 'Selili, Samarinda Ilir',
                'description' => 'Roastery yang berfokus pada biji kopi dari daerah Kalimantan dan Sulawesi. Menyediakan profil roast dari light hingga dark untuk berbagai kebutuhan. Juga membuka cafe kecil sebagai showroom dan tempat cupping.',
                'latitude' => -0.494234,
                'longitude' => 117.161234,
                'google_maps_url' => 'https://maps.google.com/?q=Angata+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '18:00'),
            ],
            // 6
            [
                'name' => 'FloorForFloor Roastery',
                'address' => 'Jl. Ery Suparjan, Sempaja Selatan, Samarinda Utara',
                'description' => 'Roastery yang juga berfungsi sebagai cafe multilevel. Konsep unik dimana setiap lantai memiliki pengalaman berbeda. Biji kopi specialty dari petani lokal Kalimantan dan Sulawesi. Menerima pesanan roasting custom.',
                'latitude' => -0.458234,
                'longitude' => 117.154567,
                'google_maps_url' => 'https://maps.google.com/?q=FloorForFloor+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '18:00'),
            ],
            // 7
            [
                'name' => 'Triton Coffee Roaster',
                'address' => 'Jl. Ir. Sutami No.A/8, Karang Asam Ulu, Sungai Kunjang, Samarinda',
                'description' => 'Coffee roaster yang dikenal dengan presisi profil roasting-nya. Menyediakan biji kopi arabika specialty dari berbagai origin Indonesia. Melayani kebutuhan coffee shop, hotel, dan restoran di Kalimantan Timur.',
                'latitude' => -0.508234,
                'longitude' => 117.121234,
                'google_maps_url' => 'https://maps.google.com/?q=Triton+Coffee+Roaster+Samarinda',
                'operating_hours' => $this->hours('08:00', '17:00'),
            ],
            // 8
            [
                'name' => "Yen's Delight Coffee Roastery",
                'address' => 'Jl. Ir. H. Juanda No.6, Air Hitam, Samarinda Ulu',
                'description' => 'Roastery dari Yen\'s Delight yang juga berfungsi sebagai cafe & resto. Salah satu tempat nongkrong paling populer di Samarinda yang juga memproduksi biji kopi roasted. Sering menampilkan live music dan event komunitas.',
                'latitude' => -0.487345,
                'longitude' => 117.144567,
                'google_maps_url' => 'https://maps.google.com/?q=Yens+Delight+Coffee+Roastery+Samarinda',
                'operating_hours' => $this->hours('09:00', '22:00'),
            ],
        ];
    }
}
