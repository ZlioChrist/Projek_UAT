<p align="center">
  <img src="public/wadahngopi.png" width="100" alt="WadahNgopi Logo">
</p>

<h1 align="center">WadahNgopi: Apex WFC Ecosystem ☕</h1>

<p align="center">
  <strong>Platform Pencarian Cafe & Roastery №1 di Indonesia — Ultra-Premium 2026 Edition</strong><br>
  <em>Bukan sekadar direktori — ini adalah kurator vibe produktivitas dan status sosial lo.</em>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%2B-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11"></a>
  <a href="https://react.dev"><img src="https://img.shields.io/badge/React-Hybrid-61DAFB?style=for-the-badge&logo=react&logoColor=white" alt="React Hybrid"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind-v4-06B6D4?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind v4"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-v3-EEBB0B?style=for-the-badge&logo=filament&logoColor=white" alt="Filament v3"></a>
  <img src="https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA Ready">
</p>

---

## ⚡ Apa Itu WadahNgopi?

WadahNgopi berevolusi dari sekadar aplikasi direktori tempat ngopi biasa menjadi **Ekosistem Validasi Sosial & Produktivitas** yang ditenagai oleh psikologi *habit-forming* dan arsitektur *engineering* tingkat dewa. Aplikasi ini dibangun buat *freelancer*, mahasiswa, dan *remote workers* yang butuh tempat *Work From Cafe* (WFC) yang bener-bener terkurasi, sekaligus jadi ajang nongkrong yang asik bareng temen.

---

## 🚀 Fitur Unggulan & Cara Kerjanya

Projek ini dilengkapi dengan **Retention Suite**, sekumpulan fitur yang didesain buat bikin *user* balik lagi dan terus berinteraksi.

### 1. 🏢 WFC Scoring Ecosystem (Vibe Produktivitas)
Sistem penilaian *Work From Cafe* yang objektif dan divalidasi.
- **Tiga Pilar Penilaian:** Pengguna bisa memberi rating terpisah untuk **Kecepatan WiFi**, **Ketersediaan Stopkontak (Listrik)**, dan **Kenyamanan Tempat**.
- **Anti-Manipulation Engine:** Sistem "Satu User Satu Rating" mencegah *review bombing* (serangan review palsu).
- **Suara Warga:** Kolom komentar (*review*) yang jujur dari komunitas.

### 2. 📊 Live Vibe Meter (Crowdsourcing Keramaian)
Pengen ke cafe tapi takut gak dapet tempat duduk?
- **Fungsi:** Menampilkan status keramaian cafe secara *real-time* ("Sepi", "Sedang", "Rame").
- **Cara Kerja:** User yang sedang berada di lokasi bisa nge-vote status keramaian. Vote ini punya **masa berlaku (decay) 4 jam**, artinya data keramaian selalu *fresh* dan aktual.
- **Validasi GPS:** Buat mencegah spam, fitur ini ngecek jarak user via GPS. Hanya user yang berada dalam **radius 100 meter** dari cafe yang bisa nge-vote.

### 3. 🤝 Tongkrongan (Voting List Kolaboratif)
Fitur janjian nongkrong tanpa ribet debat di grup WhatsApp.
- **Fungsi:** Lo bisa bikin *list* kandidat cafe (min 2, max 5) dan share link-nya ke grup WA biar temen-temen lo bisa *vote* cafe mana yang mau didatengin.
- **Frictionless Voting:** Temen lo yang buka link **TIDAK PERLU LOGIN** buat ikutan *vote*. Sistem mendeteksi pemilih unik via *device fingerprinting* (disimpan di *localStorage*).
- **Masa Aktif:** List "Tongkrongan" ini otomatis kedaluwarsa (*expired*) dalam waktu 24 jam untuk menjaga kebersihan database.

### 4. 🏅 Badge Gamification (Habit Builder)
Sistem pencapaian ala game buat bikin user rajin eksplor cafe baru.
- **Fungsi:** User dapet lencana (*badge*) yang bisa dipamerin di profil mereka.
- **Cara Kerja:** Tersedia tombol **Check-In** di halaman cafe. Saat user tekan tombol ini di lokasi (divalidasi dengan GPS radius 100m), sistem nyatet kehadiran mereka.
- **Contoh Badge:**
  - *First Timer*: Check-in pertama kali.
  - *Explorer*: Sudah check-in di 5 cafe berbeda.
  - *Night Owl*: Check-in di atas jam 9 malam.
  - *Weekend Warrior*: Sering check-in pas *weekend*.

### 5. 💾 Hybrid Bookmarking (Simpan Tanpa Login)
Sistem favorit yang *seamless* antara pengguna awam dan pengguna terdaftar.
- **Fungsi:** Simpan cafe atau *roastery* favorit biar gak lupa.
- **Cara Kerja:** Kalau lo cuma pengunjung biasa (Guest), data favorit disimpan di memori HP (`localStorage`). Kalau lo akhirnya daftar dan **Login**, data favorit dari HP lo akan **otomatis tersinkronisasi** ke *database* utama (PostgreSQL), lalu dihapus dari HP. Jadi datanya aman tersimpan di *cloud* tanpa hilang.

### 6. 🏪 Pencarian Cerdas & Geo-Location
- Menggunakan fitur **FULLTEXT Search** MySQL untuk pencarian nama dan deskripsi yang ngebut.
- Fitur "Terdekat" menggunakan perhitungan rumus **Haversine** untuk menghitung jarak antara koordinat GPS user dan latitude/longitude cafe di database.

### 7. 🎯 Cafe Roulette (Roda Keberuntungan)
Kalau bingung mau kemana, klik tombol *Roulette*! Sistem bakal ngacak cafe-cafe yang **sedang buka saat itu juga** dan memberikan rekomendasi spot nongkrong secara acak dengan animasi putaran roda dan *haptic feedback* (getar) di HP lo.

### 8. 📱 PWA (Progressive Web App)
WadahNgopi gak perlu di-download dari Play Store.
- **Instalasi:** Cukup buka dari *browser* HP (Chrome/Safari), lalu pilih "Add to Home Screen". Aplikasi akan terinstal di HP dengan ukuran di bawah 1 MB!
- **Offline-capable:** Menggunakan *Service Worker* dan *Manifest* untuk nge-cache aset statis, jadinya aplikasi tetep kerasa enteng, layar penuh tanpa URL bar, dan kerasa kayak aplikasi *Native*.

### 9. 🛡️ Filament Admin Dashboard
Panel *backend* eksklusif buat admin atau *owner* cafe.
- **Role-Based Access:** Ada batasan akses antara Super Admin, Admin Cafe, dan Penulis Artikel.
- **CRUD Super Cepat:** Manajemen tambah/edit/hapus foto cafe, jam operasional, dan info detail secara mudah dan intuitif.

### 10. 🛒 Pemesanan dan pengambilan sesuai UAT
- Katalog menu jual memiliki harga dan stok terpisah dari foto galeri menu; guest dapat menambah ke keranjang dan checkout tanpa akun.
- Stok direservasi selama pembayaran menunggu verifikasi, lalu dikurangi tepat sekali setelah owner kedai mengonfirmasi bukti QR. Bukti transfer tidak otomatis dianggap lunas.
- Tabel inti katalog mengikuti ERD capstone (`products` dan `order_items.product_id`); migration upgrade mempertahankan data dari versi awal (`menu_items`).
- Setelah pembayaran lunas, pesanan hanya boleh masuk status **Sedang Disiapkan** jika satu print job nota tercatat berhasil; hasil cetak dan pelapornya diaudit.
- Pembeli menerima kode unik untuk nota dan pelacakan; status bergerak berurutan sampai **Siap Diambil** dan staf mencocokkan kode saat serah-terima.
- Dashboard kedai membatasi pesanan menurut kepemilikan cafe, menyediakan polling status, riwayat, antrean nota, cetak ulang, dan catatan kegagalan cetak.
- Browser web membuka dialog cetak biasa dan tidak dapat menjamin silent-print/deteksi printer. Cetak termal otomatis memerlukan print bridge lokal yang dikonfigurasi terpisah.

---

## 🧰 Modern Tech Stack

Kombinasi teknologi yang dipakai dijamin *future-proof* dan *blazing fast*.

| Layer | Teknologi | Alasan Pemilihan |
|:------|:----------|:-------------|
| **Backend Core** | Laravel 12 (PHP 8.2.12+) | Backbone sistem yang stabil, aman, dan cocok dengan target XAMPP 8.2.12. |
| **Frontend Utama** | Livewire 3 + Alpine.js | Bikin interaksi web terasa seperti *Single Page Application* (SPA) tanpa perlu *setup* framework JS yang berat. |
| **Frontend Kompleks** | React.js (Hybrid) | Buat komponen UI interaktif kayak *Vibe Meter*, *Check-In*, dan sistem *Rating* ditenagai oleh React + Vite biar makin *smooth*. |
| **Admin Panel** | Filament v3 | *Zero-config* dashboard paling *powerful* buat Laravel saat ini. |
| **Styling** | Tailwind CSS v4 | *Utility-first CSS* dengan konsep desain *Glassmorphism* (transparan + blur) khas UI Ultra-Premium 2026. |
| **Database** | MySQL / MariaDB | Pencarian lokasi menggunakan koordinat latitude/longitude dengan fallback Haversine yang lintas versi. |
| **Asset Bundler**| Vite 7 | Compile aset statis secepat kilat dengan *Hot Module Replacement* (HMR). |

---

## 🛡️ Security & Engineering Standard

Project ini gak asal jalan, tapi dibangun pakai standar keamanan tinggi:
- **Anti N+1 Queries:** Kode database sangat dioptimasi (pakai *Eager Loading* `->with()`) biar halaman gak lambat walau ngambil banyak data relasi.
- **CSP Headers & Rate Limiting:** Proteksi ketat dari serangan XSS dan batas permintaan server (misal: cuma bisa bikin Tongkrongan 3 kali sehari) buat cegah *spamming*.
- **IDOR Protection:** Validasi kepemilikan data—user A gak bakal bisa hapus rating user B.
- **Atomic View Counter:** Sistem hitung "Dilihat Berapa Kali" pakai metode tarikan berkala (*batching*) biar *database* gak meledak walau banyak yang klik dalam waktu bersamaan.

---

## 🚀 Panduan Setup & Instalasi (Development)

Buat lo yang mau *clone* dan ngerjain project ini di laptop/PC lo, ikutin langkah ini:

**Pakai XAMPP 8.2.12?** Ikuti [panduan khusus XAMPP 8.2.12](PANDUAN_XAMPP_8_2_12_ID.md) untuk memperbaiki dependency, menyiapkan MySQL, GPS, serta akun admin. Arsip ini tidak berisi database produksi. Hindari `migrate --seed` tanpa meninjau seedernya karena seed bawaan menyertakan akun demo.

### Prasyarat
- PHP 8.2 ke atas (Direkomendasikan PHP 8.4)
- Composer 2.x
- Node.js versi 20+
- Database MySQL atau MariaDB (XAMPP)
- (Opsional) Laravel Herd untuk mempermudah jalanin PHP dan Node.

### Langkah Instalasi (Local)

1. **Clone repository ini**
   ```bash
   git clone https://github.com/2343087/wadahngopi.git
   cd wadahngopi
   ```

2. **Install dependency PHP & Node.js**
   ```bash
   composer install
   npm install
   ```

3. **Setup File Environment (.env)**
   Copy file pengaturan dasar dan generate kunci aplikasi.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Buka file `.env` pakai text editor, lalu atur koneksi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai MySQL di laptop lo.*

4. **Jalankan migrasi dan data referensi aman**
   Jalankan migrasi tanpa seeder akun demo. Badge dan fasilitas adalah data referensi yang dapat di-seed terpisah.
   ```bash
   php artisan migrate
   php artisan db:seed --class=BadgeSeeder
   php artisan db:seed --class=FacilitiesSeeder
   ```

5. **Buat akun admin**
   Untuk akses super-admin/developer dan akun ber-password milik sendiri, ikuti langkah Tinker pada [panduan XAMPP](PANDUAN_XAMPP_8_2_12_ID.md#3-buat-akun-administrator-dengan-akses-luas). Jangan gunakan akun demo dari seeder.

6. **Build Aset Frontend**
   Compile semua file React, Tailwind, dan CSS lo.
   ```bash
   npm run build
   # atau untuk mode development berjalan: npm run dev
   ```

7. **Nyalakan Server**
   ```bash
   php artisan serve
   ```
   *Akses di browser: `http://localhost:8000`*

---

## 🌐 Deploy ke Production (Contoh: Hostinger)

Kalau udah siap tayang (*live*) ke internet:
1. Beli paket hosting (Minimal yang mendukung SSH dan Node.js).
2. Via SSH, *clone* repository lo di dalam folder `public_html`.
3. Jalankan `composer install --no-dev --optimize-autoloader` dan `npm run build` di server.
4. Setting `.env` dengan kredensial database *live* dari Hostinger. Ubah `APP_ENV=production` dan `APP_DEBUG=false`.
5. PENTING: Karena Shared Hosting rata-rata gak punya *Redis*, pastikan `CACHE_STORE=file` dan `SESSION_DRIVER=file`.
6. Migrasi database: `php artisan migrate --force`.
7. Optimasi performa server:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
8. Arahkan *Document Root* domain lo ke folder `/public` di pengaturan panel hosting.

---

## 🏆 Kenapa WadahNgopi Lebih Unggul dari Kompetitor?

- **Dibanding Google Maps:** WadahNgopi punya data khusus cafe, info *vibe* colokan dan WiFi, dan *interface* yang *Gen-Z friendly*. Maps terlalu *general*.
- **Dibanding TikTok/IG:** Kalau di sosmed lo nyari dari *hashtag* susah disaring. Di WadahNgopi, lo bisa langsung filter cafe mana yang *"Buka Sekarang"*, *"Ada WiFi Kenceng"*, dan yang jaraknya paling deket pakai peta.
- **Fair-Play Exposure:** Di aplikasi lain, cafe yang ngebayar mahal selalu tampil di atas. WadahNgopi ngacak urutan cafe (Shuffle) untuk tiap sesi *user*, sehingga UMKM dan cafe lokal kecil tetep dapet *exposure* yang adil!

---

## 👑 Tim Pengembang & Arsitek

WadahNgopi dikembangkan oleh *engineers* yang percaya bahwa koding bukan sekadar ngetik, tapi merancang sistem yang kuat (*secure*), cepat, dan memanjakan mata (*Premium Aesthetics*).

- **Chomelius Delon** — *Lead Architect & Apex Engineer* ([GitHub](https://github.com/2343087))
- **RisyalPramudititi Ricol** — *Core Contributor & Feature Engineer* ([GitHub](https://github.com/RisyalPramudititia))

---

<p align="center">
  <strong>© 2026 WadahNgopi — by AK Kreatif</strong><br>
  <em>Diseduh dengan ❤️ dan baris kode presisi dari Kalimantan.</em><br><br>
  <code>☕ brew(); code(); repeat();</code>
</p>
