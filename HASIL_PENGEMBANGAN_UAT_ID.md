# Hasil Pengembangan Modul Pemesanan — WadahNgopi

## Ringkasan

Modul pemesanan dikembangkan berdasarkan **DokumenUATEPD.docx**. Cakupannya meliputi katalog dan stok menu jual, keranjang/checkout akun maupun guest, bukti pembayaran QR yang diverifikasi manual kedai, status/pelacakan, nota digital, pencatatan pekerjaan cetak, cetak ulang, dan pickup dengan kode unik.

**Ini laporan implementasi dan verifikasi kode, bukan berita acara penerimaan resmi.** Dokumen UAT mensyaratkan seluruh 20 skenario lulus di tempat mitra dan nilai kuesioner mutu ISO/IEC 9126 minimal 85%. Uji printer fisik, 30 pengukuran latensi di Wi-Fi kedai, pengalaman pembeli/barista, dan kuesioner belum dilakukan di sandbox.

## Matriks keterlacakan 20 skenario

| Kode UAT | Pengembangan/hasil pemeriksaan | Status |
|---|---|---|
| UAT-1.1 Menu Kedai | Katalog jual terpisah dari galeri foto lama; halaman cafe publik menampilkan item, harga, dan stok. Test memastikan menu tampil tanpa login. | Teruji otomatis; pemeriksaan visual ponsel tetap perlu |
| UAT-1.2 Produk Habis | Stok tersedia dihitung sebagai stok fisik dikurangi reservasi; saat 0 muncul label **Habis** dan tombol tambah dinonaktifkan. | Teruji otomatis |
| UAT-1.3 Pesan dengan Akun | Checkout akun menyimpan `user_id`, mendukung lebih dari satu item, snapshot harga, total, dan riwayat pesanan berisi item/kuantitas. | Teruji otomatis |
| UAT-1.4 Pesan tanpa Akun | Checkout guest menyimpan nama/WhatsApp, membuat kode pesanan, dan dapat dilacak. Nomor Indonesia dinormalisasi ke awalan `62`. | Teruji otomatis |
| UAT-1.5 Pesanan Tidak Wajar | Keranjang kosong dan kuantitas melebihi stok ditolak sebelum order dibuat atau stok diubah. Checkout memakai idempotency key agar retry tidak membuat order/reservasi ganda. | Teruji otomatis |
| UAT-2.1 Bayar lewat QR | QR/instruksi per cafe, unggah bukti privat, validasi nominal tepat, referensi, dan status menunggu verifikasi tersedia. | Alur aplikasi teruji; pembayaran e-wallet/bank sungguhan tetap uji manual |
| UAT-2.2 Cek Bayar oleh Kedai | Owner cafe memeriksa bukti dan melakukan konfirmasi manual; konfirmasi mengubah status, menyimpan pelaku/waktu verifikasi, mengurangi stok tepat sekali, serta membuat pekerjaan nota. | Teruji otomatis |
| UAT-2.3 Belum Bayar Terkunci | Status `preparing` diblokir jika belum lunas **atau** belum ada print job dengan hasil fisik `printed`; kegagalan cetak juga tidak membuka preparation. | Teruji otomatis |
| UAT-2.4 Riwayat Transaksi | Pembeli terautentikasi melihat cafe, kode, waktu, status, total, nama item dan jumlah dari snapshot order. | Teruji otomatis |
| UAT-2.5 Anti Bayar Dobel | Referensi pembayaran unik; pengiriman referensi/konfirmasi ulang ditolak. Konfirmasi kedua tidak memotong stok atau membuat pekerjaan cetak otomatis baru. | Teruji otomatis |
| UAT-3.1 Cetak Otomatis | Pembayaran lunas membuat print job otomatis dengan payload nota snapshot, dan sistem menunggu hasil `printed` sebelum mengizinkan preparation. Halaman saat ini membuka dialog `window.print()`. | **Parsial:** belum mengirim perintah langsung ke printer thermal; perlu print bridge sesuai perangkat |
| UAT-3.2 Cetak Ulang | Kedai dapat membuat record reprint dengan snapshot yang sama; status pesanan tetap lunas dan tidak kembali ke antrean dapur. | Alur aplikasi teruji; keluaran printer perlu uji manual |
| UAT-3.3 Printer Mati/Kertas Habis | Record cetak menyimpan state `pending/failed/printed`, jumlah percobaan, error, waktu hasil, dan pelapor. Cetak gagal tidak membuka preparation; reprint menghasilkan job baru. | **Parsial:** status gagal saat ini dilaporkan staf; belum ada printer-agent untuk deteksi/retry otomatis |
| UAT-4.1 Status Sinkron | Transisi dibatasi: lunas → disiapkan → siap diambil → selesai; polling endpoint status memuat perubahan terbaru. | Transisi teruji otomatis; sinkronisasi dua perangkat nyata perlu uji manual |
| UAT-4.2 Lacak Pesanan | Guest dengan kode bearer dapat melihat nota/status dan endpoint polling mengirim data tanpa cache. | Teruji otomatis |
| UAT-4.3 Kecepatan Status | Halaman melakukan polling berkala 3 detik. | **Belum diukur:** perlu 30 percobaan di jaringan kedai; rata-rata/puncak dan persentase berhasil harus dicatat saat UAT |
| UAT-4.4 Kedai Terpisah | Query order owner dibatasi ke cafe miliknya; akses owner lain menghasilkan 404, sedangkan developer dapat melihat seluruh cafe yang diotorisasi. | Teruji otomatis |
| UAT-5.1 Ambil Tanpa Antre | Kode pesanan hanya menyelesaikan pesanan berstatus `ready`; waktu dan riwayat status dicatat. | Teruji otomatis |
| UAT-5.2 Tolak Kode Salah | Kode yang tidak dikenal/sudah selesai serta order yang belum siap ditolak tanpa mengubah status. | Teruji otomatis |
| UAT-5.3 Ambil tanpa Akun | Guest tidak memerlukan akun untuk memperoleh nota/kode; kedai menyelesaikan order memakai kode yang sama. | Teruji otomatis pada alur guest dan pickup |

## Keputusan kesiapan

- **Belum layak menandatangani berita acara “DITERIMA”.** UAT-3.1, UAT-3.3, dan UAT-4.3 memerlukan perangkat/jaringan nyata. Nilai kuesioner ISO/IEC 9126 harus berasal dari penilai manusia; nilainya tidak dapat disimpulkan dari test otomatis.
- Browser tidak memberi aplikasi web hak untuk mencetak diam-diam atau membaca status kertas/printer. Untuk memenuhi cetak otomatis fisik, pilih dan pasang print bridge di komputer kasir (misalnya QZ Tray dengan konfigurasi sertifikat/driver yang sesuai), lalu uji dari browser kasir. Jangan menganggap dialog `window.print()` sebagai bukti struk sudah keluar.
- Jika print bridge otomatis belum tersedia saat uji, catat UAT-3.1/UAT-3.3 sebagai catatan/gagal sesuai rubrik mitra, bukan menandainya lulus.
- Proposal memuat state pembayaran `EXPIRED`, tetapi belum menentukan batas waktu maupun perlakuan order yang sudah mengirim bukti namun belum diperiksa. Sistem belum mengekspirasi order otomatis agar stok pelanggan tidak dilepas berdasarkan batas waktu yang belum disepakati.

## Pengukuran Scientific Method (30 percobaan)

Gunakan lembar [`UAT_LATENSI_30_RUNS.csv`](UAT_LATENSI_30_RUNS.csv) dan pertahankan perangkat, browser, jaringan kedai, versi server, akun uji, nominal, serta skenario status/cetak yang sama. Catat timestamp dengan zona waktu yang sama; untuk latensi antardevais, sinkronkan jam atau ukur melalui satu pencatat eksternal agar clock skew tidak dianggap sebagai latensi.

- `status_latency_ms` = waktu status pertama terlihat di sisi pembeli − waktu perubahan status pada kedai.
- `print_trigger_latency_ms` = waktu nota fisik keluar − konfirmasi pembayaran lunas. Catat juga timestamp `print_job_created_at_iso` untuk membedakan keterlambatan antrean aplikasi dan perangkat printer.
- Untuk status, P50 gunakan `=MEDIAN(H2:H31)`, P95 `=PERCENTILE.INC(H2:H31,0.95)`, dan success rate `=COUNTIF(I2:I31,TRUE)/30*100%`. Untuk cetak, kolom latency adalah M dan keberhasilan N: gunakan rumus yang sama pada `M2:M31` dan `N2:N31`. Catat percobaan gagal, jangan dihapus dari sampel.
- Nilai hasil tidak diisi oleh sandbox karena pengukuran harus dilakukan dari klien dan printer mitra yang sebenarnya.

## Perubahan penting dalam source

- ERD inti diselaraskan ke `users`, `cafes`, `products`, `orders`, dan `order_items.product_id`; migration rename mempertahankan data dari versi sebelumnya yang menggunakan `menu_items`/`menu_item_id`. Tabel pendukung pembayaran, riwayat status, dan print job memisahkan bukti serta audit.
- Transaksi atomik dan penguncian baris stok; harga/nama disimpan sebagai snapshot untuk nota dan riwayat.
- Pembayaran QR bersifat **manual**: unggahan bukti tidak otomatis berarti lunas.
- Scope owner, batas status lunas→nota tercetak→preparing, idempotency checkout, kode pickup, tracking publik dengan rate limit/no-store, halaman riwayat akun, nota, serta reprint. Hasil print manual menyimpan waktu dan user pelapor.
- Kompatibilitas migration disesuaikan untuk XAMPP/MariaDB: waktu operasional menjadi kolom `TIME` terindeks yang disinkronkan model; pencarian lokasi menggunakan latitude/longitude dan Haversine ketika spatial berbeda. Koordinat 0° yang valid tidak lagi dibuang.

## Uji yang dapat dijalankan

Test pemesanan baru berada di `tests/Feature/Ordering/OrderingUatTest.php`. Jalankan pada database uji yang terpisah setelah `.env` dan `APP_KEY` tersedia:

```bash
php artisan test --filter=OrderingUatTest
php artisan test
```

Verifikasi akhir pada source ini: **8 test UAT lulus (102 assertion)**; seluruh suite **206 test lulus (437 assertion)** pada MariaDB 10.11; rollback dan pemasangan ulang migration products/audit berhasil; Composer validate dan platform check berhasil; `npm run build`, Blade view cache, serta lint 195 file PHP berhasil. Composer memberi dua peringatan skema non-blocking (constraint Filament persis `3.3` dan constraint Predis tidak dibatasi). Runtime sandbox menggunakan PHP 8.3.6; `composer.lock`/platform target dikunci untuk PHP 8.2.12, sehingga XAMPP 8.2.12 Windows tetap perlu diverifikasi langsung.

## Batas akses data

Paket source tidak memuat database live, data pembeli, atau backup situs produksi. Akun `developer` hanya memberi akses luas terhadap data pada database yang Anda konfigurasi sendiri. Untuk mengakses data live, gunakan backup SQL/kredensial resmi yang memang Anda berhak kelola; jangan mengubah pembatasan untuk membuka data pihak lain.
