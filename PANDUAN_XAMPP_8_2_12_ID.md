# Panduan WadahNgopi — XAMPP 8.2.12 (Windows)

## Catatan versi dan isi arsip

- Yang dimaksud versi PHP pada XAMPP 8.2.12 adalah **PHP 8.2.12** (bukan PHP “8.22”). Kode aplikasi meminta PHP `^8.2` dan memakai Laravel 12.
- Arsip awal mengunci beberapa dependency yang meminta PHP 8.3/8.4 serta plugin React yang tidak cocok dengan Vite 7. Keduanya sudah diselaraskan di paket ini. `composer.lock` sekarang ditargetkan ke PHP 8.2.12, dan plugin React diselaraskan dengan Vite 7.
- Aplikasi ini **tidak menyertakan salinan database produksi atau data pengguna dari website live**. Isinya kode aplikasi, migration, dan beberapa seeder contoh. Data yang terlihat setelah instalasi adalah data yang Anda masukkan/import sendiri atau data contoh.
- Jangan jalankan `php artisan migrate --seed` tanpa meninjau seedernya. Seeder bawaan proyek membuat akun demo berkata sandi yang sudah ditentukan. Petunjuk di bawah memakai `migrate` saja, lalu membuat akun administrator milik Anda sendiri.

## 1. Persiapan XAMPP

1. Pasang dan buka **XAMPP 8.2.12**. Jalankan **MySQL** dari XAMPP Control Panel.
2. Di `http://localhost/phpmyadmin`, buat database bernama `wadahngopi` dengan collation `utf8mb4_unicode_ci`.
3. Pastikan PHP CLI yang dipakai Composer adalah PHP milik XAMPP. Buka Command Prompt baru dan cek:

   ```bat
   php -v
   where php
   composer --version
   node -v
   npm -v
   ```

   `php -v` harus menunjukkan **8.2.12**. Jika bukan, tambahkan `C:\xampp\php` ke `PATH` Windows, letakkan di atas instalasi PHP lain, lalu buka Command Prompt baru. Node.js 20 atau yang lebih baru dan Composer 2.x juga dibutuhkan untuk membangun proyek.

4. Buka `C:\xampp\php\php.ini`, pastikan ekstensi berikut aktif (hapus `;` di depannya jika ada), lalu restart Apache di XAMPP:

   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=mbstring
   extension=mysqli
   extension=pdo_mysql
   extension=openssl
   extension=zip
   extension=xml
   extension=dom
   extension=exif
   extension=bcmath
   ```

   Beberapa ekstensi di atas mungkin sudah aktif atau ditulis dengan nama modul yang berbeda; cek hasil `php -m` dan Composer bila instalasi mengeluhkan ekstensi.

## 2. Pasang source dan dependency

1. Ekstrak folder proyek ke, misalnya, `C:\xampp\htdocs\wadahngopi`.
2. Buka Command Prompt di folder tersebut:

   ```bat
   cd C:\xampp\htdocs\wadahngopi
   composer install
   copy .env.example .env
   ```

3. Buka `.env`, lalu atur paling sedikit nilai berikut untuk instalasi lokal XAMPP:

   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=wadahngopi
   DB_USERNAME=root
   DB_PASSWORD=

   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   ```

   Jika MySQL XAMPP Anda memakai kata sandi root, isi `DB_PASSWORD` sesuai konfigurasi lokal Anda. Jangan gunakan konfigurasi lokal ini untuk server publik.

4. Buat kunci aplikasi, tabel database, dan aset frontend:

   ```bat
   php artisan key:generate
   php artisan migrate
   php artisan db:seed --class=BadgeSeeder
   php artisan db:seed --class=FacilitiesSeeder
   npm ci
   npm run build
   php artisan storage:link
   ```

   Migration proyek yang disesuaikan menyimpan jam operasional di kolom `TIME` biasa dan koordinat lintas versi memakai fallback latitude/longitude, sehingga instalasi baru tidak memerlukan strict spatial SRID MySQL 8. Jika Anda memperbarui database lama yang sudah pernah menjalankan migration, perubahan file migration lama tidak mengubah tabel yang sudah tercatat; backup dahulu dan jangan menjalankan `migrate:fresh` pada database yang berisi data.

## 3. Buat akun administrator dengan akses luas

Aplikasi memiliki dashboard Filament di `/admin`. Peran `developer` adalah peran super-admin di kode proyek: peran ini dapat mengelola pengguna dan tidak terkena pembatasan kepemilikan kafe. Peran `admin`/owner dapat dibatasi ke data miliknya. Buat akun developer hanya untuk pemilik/pengelola tepercaya.

Di Command Prompt, jalankan:

```bat
php artisan tinker
```

Di prompt Tinker, buat pengguna Anda sendiri (ganti nama, email, dan kata sandi di bawah dengan nilai milik Anda; gunakan kata sandi kuat dan jangan kirimkan kata sandi itu ke siapa pun):

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Nama Anda',
    'email' => 'email-anda@example.com',
    'password' => Hash::make('GANTI_DENGAN_KATA_SANDI_KUAT'),
    'role' => 'developer',
]);
```

Keluar dari Tinker dengan `exit`, kemudian buka:

```text
http://127.0.0.1:8000/admin
```

Login dengan email dan kata sandi yang Anda buat. Akun developer adalah akses sangat luas; jangan membuatnya untuk orang yang tidak dipercaya. Jangan memakai akun demo bawaan. Jika akun email tersebut sudah ada, gunakan `User::where('email', 'email-anda@example.com')->firstOrFail()` lalu ubah `role`-nya menjadi `developer` di Tinker, daripada membuat duplikat.

## 4. Jalankan website dan gunakan lokasi terkini

Untuk cara termudah di komputer lokal, biarkan MySQL tetap berjalan, kemudian jalankan:

```bat
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Gunakan executable PHP XAMPP secara eksplisit. Pada komputer ini, perintah `php` biasa memilih `C:\php\php.exe`, yang untuk server web menggunakan `C:\php\php.ini` dengan batas eksekusi 30 detik. Apache XAMPP memakai `C:\xampp\php\php.ini`; keduanya konfigurasi PHP yang berbeda. Perintah di atas menjalankan website dengan PHP milik XAMPP dan menghindari perbedaan konfigurasi tersebut. Periksa pilihan `php` di terminal dengan `where.exe php`.

Agar status buka/tutup dan label waktu berikutnya selalu mengikuti website resmi, jalankan sinkronisasi awal:

```bat
C:\xampp\php\php.exe artisan app:sync-official-cafe-status
```

Perintah tersebut menyimpan snapshot status ke database (`official_is_open`, `official_status_text`, dan `official_status_checked_at`). Agar pembaruan berjalan otomatis tanpa membiarkan terminal scheduler terbuka, buat tugas Windows melalui **Task Scheduler**:

1. Pilih **Create Basic Task** dan beri nama `WadahNgopi Laravel Scheduler`.
2. Atur pemicu **Daily**, lalu di **Advanced settings** aktifkan **Repeat task every: 1 minute** dan pilih **Indefinitely**.
3. Pilih aksi **Start a program**. Isi **Program/script** dengan `C:\xampp\php\php.exe`, **Add arguments** dengan `artisan schedule:run`, dan **Start in** dengan `C:\xampp\htdocs\wadahngopi_coba`.
4. Simpan tugas dan pilih **Run** untuk menguji. Pastikan status terakhir di database diperbarui; komputer harus menyala, terhubung internet, dan MySQL XAMPP aktif.

Laravel sudah menjadwalkan sinkronisasi status resmi setiap lima menit. Task Scheduler menjalankan pemeriksa jadwal setiap menit; perintah `artisan serve` saja tidak menjalankan pemeriksa tersebut. Alternatif selama sesi pengembangan adalah menjalankan `C:\xampp\php\php.exe artisan schedule:work` di terminal kedua. Akses website di `http://127.0.0.1:8000`; dashboard admin ada di `/admin`.

**Jadwal rutin per hari berbeda dari status saat ini.** Halaman publik resmi hanya menampilkan apakah cafe sedang buka/tutup dan waktu buka/tutup berikutnya; jam mingguan diperiksa terpisah dari profil Google Maps yang tertaut. Database lokal telah diisi jam harian yang dapat diverifikasi untuk **26 cafe** dan **3 roastery**, serta tiga cafe yang tercatat buka 24 jam setiap hari. Tempat yang tidak memiliki jadwal Maps yang dapat dicocokkan tetap kosong—jangan mengisi jam perkiraan. Formulir `/admin` kini menyediakan isian terpisah untuk Senin sampai Minggu; jam lama weekday/weekend juga tetap dibaca sebagai fallback. Filter **Sedang Buka** memakai jadwal harian ketika snapshot resmi lebih lama dari 15 menit.

Browser akan meminta izin lokasi saat fitur terdekat atau fitur lokasi digunakan. Pilih **Allow/Izinkan**. Geolocation browser bekerja di **localhost/127.0.0.1** atau situs **HTTPS**. Jika dibuka dari HP melalui alamat IP LAN atau domain HTTP biasa, browser umumnya memblokir lokasi; gunakan domain HTTPS untuk pengujian dari HP. Fitur ini mengambil lokasi setelah tindakan pengguna dan izin browser—bukan pelacakan latar belakang yang selalu aktif. Check-in/voting tertentu juga menggunakan kedekatan GPS sesuai implementasi proyek.

Untuk memakai **Terdekat**, buka halaman Jelajahi atau Roastery melalui `http://localhost:8000` / `http://127.0.0.1:8000`, tekan **Terdekat**, lalu izinkan akses lokasi di browser. Jika izin pernah ditolak, ubah izin **Lokasi** untuk situs ini dari ikon di kiri alamat browser, lalu coba lagi. Pastikan layanan lokasi perangkat juga aktif.

Lokasi browser adalah lokasi pengunjung; setiap cafe/roastery juga harus memiliki koordinatnya sendiri agar jarak bisa dihitung. Koordinat yang tersedia sudah diisi dari tautan Google Maps pada data lokal: **42 dari 70 cafe** dan **6 dari 6 roastery** terbit. Untuk mengisi koordinat kosong dari tautan Google Maps tersimpan, jalankan `C:\xampp\php\php.exe artisan app:sync-official-place-coordinates`; perintah ini hanya mengisi pasangan koordinat yang kosong dan melaporkan tautan yang tidak dapat diproses. Cafe yang tersisa perlu dilengkapi manual di `/admin` menggunakan **Latitude** dan **Longitude** dari Google Maps (klik kanan lokasi di peta untuk menyalin koordinat). Tombol ambil lokasi pada formulir admin hanya tepat digunakan jika perangkat sedang berada di lokasi tempat tersebut.

Jika permintaan `/livewire/update` kembali melewati 30 detik, berarti request PHP server melewati batas waktu eksekusi; `ClassLoader.php` dapat menjadi lokasi PHP kehabisan waktu, bukan akar masalahnya. Query terdekat sudah dipersempit dengan batas koordinat sebelum menghitung jarak. Pastikan server yang dijalankan memakai `C:\xampp\php\php.exe` seperti perintah di atas. Jika memakai Apache, periksa **Loaded Configuration File** dan `max_execution_time` di PHPInfo XAMPP; restart Apache setelah mengubah `C:\xampp\php\php.ini`. Menaikkan batas waktu hanya sementara—jangan jadikan pengganti pemeriksaan query lambat.

Alternatifnya, arahkan konfigurasi VirtualHost Apache ke folder `public` proyek, bukan root folder Laravel. Jangan membuka folder proyek tanpa document root `public` pada server publik.

## 5. Menyiapkan alur order sesuai UAT

1. Login sebagai `developer` di `/admin`, buat atau pilih cafe, tetapkan owner, dan pastikan status cafe **published** agar dapat dipesan secara publik.
2. Buka **Menu & Stok** di panel `/admin`, lalu tambahkan menu jual (nama, harga, stok fisik, foto opsional, dan status aktif). Data produk disimpan pada tabel inti `products`, sedangkan `order_items.product_id` menghubungkan snapshot transaksi. Ini berbeda dari `Foto Daftar Menu`/galeri lama; foto katalog tidak mempunyai harga maupun stok. Migration upgrade mengubah tabel `menu_items` versi paket sebelumnya menjadi `products` tanpa menghapus baris order.
3. Edit cafe dan pada bagian **Pembayaran Pesanan** unggah QR pembayaran kedai dan isi instruksi. Jika QR belum dipasang, pembeli tidak dapat mengirim bukti lewat halaman pesanan.
4. Buka halaman cafe publik, pilih menu, tambah ke keranjang, lalu checkout. Checkout mendukung guest; isi nama dan nomor WhatsApp. Simpan kode pada nota digital untuk melacak/mengambil pesanan.
5. Kedai membuka `/owner/orders` (atau tautan **Pesanan kedai** setelah login), memeriksa bukti transfer, lalu mengonfirmasi hanya setelah dana benar-benar masuk. Stok direservasi saat order dibuat, tetapi stok fisik baru dikurangi saat pembayaran diverifikasi. Pembatalan sebelum lunas melepas reservasi.
6. Setelah lunas, konfirmasi membuka nota. Cetak fisik, lalu pilih **Tandai cetak berhasil** hanya jika struk benar-benar keluar. Sistem memblokir **Sedang Disiapkan** bila belum ada print job `printed`; bila printer gagal, catat error dan pulihkan printer/cetak ulang dahulu. Setelah tercatat berhasil, staf memperbarui urutan **Sedang Disiapkan → Siap Diambil → Selesai**.
7. Saat serah-terima, masukkan kode dari nota pada form **Cocokkan kode pengambilan**; pesanan yang belum siap atau kode salah akan ditolak. Pembeli dapat membuka `/order-tracking` tanpa akun; halaman status melakukan polling setiap beberapa detik.
8. Saat ini halaman nota membuka dialog cetak browser. Browser standar tidak bisa mencetak diam-diam atau mendeteksi kertas/USB printer. Untuk cetak thermal otomatis tanpa dialog, perlu print bridge lokal yang disesuaikan dengan merek/model dan koneksi printer kasir; bridge tersebut belum disediakan oleh arsip ini.

Pembuktian transfer disimpan pada disk privat dan hanya dapat diunduh owner cafe yang berwenang. QR cafe/unggahan produk memakai storage publik; jalankan `php artisan storage:link` bila belum ada symlink.

## 6. Melihat, mengelola, mencadangkan, dan mengimpor seluruh data milik Anda

- Dari aplikasi: masuk sebagai developer di `/admin`. Menu panel menyediakan pengelolaan entitas aplikasi yang sudah dibuat—seperti kafe, roastery, kota, fasilitas, informasi/artikel, dan pengguna sesuai resource dan role pada source.
- Untuk melihat tabel mentah, ekspor seluruh database, atau impor backup: gunakan phpMyAdmin lokal, pilih database `wadahngopi`, lalu gunakan tab **Browse / Export / Import**. Selalu ekspor backup sebelum mengimpor atau mengubah database.
- Source ZIP ini tidak memberikan hak akses atau salinan ke database website lain/website live. Untuk memuat data live yang memang Anda miliki/berwenang kelola, Anda perlu ekspor SQL atau akses resmi ke database tersebut. Import hanya backup yang Anda berwenang gunakan; jangan mengubah sistem izin untuk membaca data pengguna pihak lain.
- Seeder `SamarindaCafeSeeder` berisi data katalog contoh dan dapat membuat akun demo jika dipakai pada basis data kosong. Tinjau file itu dan kredensial seed sebelum menjalankannya; jangan pakai pada database publik/produksi.

## 7. Pemecahan masalah singkat

- **Composer menampilkan PHP tidak cocok:** cek `where php`, `php -v`, pastikan `C:\xampp\php` yang teratas, kemudian buka terminal baru dan jalankan `composer install` lagi.
- **`could not find driver` / PDO MySQL:** aktifkan `pdo_mysql` dan `mysqli` di `php.ini` yang benar-benar digunakan CLI.
- **CSS/JS tidak tampil:** jalankan `npm ci` lalu `npm run build` kembali, pastikan `public/build/manifest.json` dibuat.
- **Lokasi tidak muncul:** izinkan location di pengaturan browser, aktifkan Location Services/GPS perangkat, buka di localhost atau HTTPS, dan periksa koordinat kafe di database.
- **Login admin ditolak:** pastikan akun memiliki `role = developer`, database `.env` mengarah ke `wadahngopi`, dan cache config dibersihkan dengan `php artisan optimize:clear` setelah mengubah `.env`.
- **Menu tidak muncul:** pastikan cafe published, menu jual aktif, stok tersedia (stok fisik dikurangi reservasi), dan Anda tidak hanya mengunggah foto galeri lama.
- **Pesanan lunas tetapi tombol mulai siapkan belum tersedia:** cetak nota fisik, lalu catat hasil sebagai berhasil. Dialog browser saja bukan bukti printer mengeluarkan struk.
- **Pembeli sudah transfer tetapi status belum berubah:** status baru menjadi Lunas setelah owner memeriksa dana dan menekan konfirmasi; bukti unggahan sendiri bukan verifikasi otomatis.

## 8. Validasi paket

Source menyediakan feature test pemesanan di `tests/Feature/Ordering/OrderingUatTest.php`; jalankan `php artisan test --filter=OrderingUatTest` dengan database MySQL/MariaDB uji dan APP_KEY yang valid. Validasi di sandbox dilakukan terhadap MariaDB 10.11, bukan XAMPP Windows Anda. Uji lagi pada XAMPP 8.2.12, izin geolokasi browser dan printer fisik kasir sebelum UAT dinyatakan lulus.
