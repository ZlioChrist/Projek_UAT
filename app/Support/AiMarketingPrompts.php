<?php

namespace App\Support;

class AiMarketingPrompts
{
    /**
     * @return array<int, array{title: string, prompt: string}>
     */
    public function quickTools(): array
    {
        return [
            [
                'title' => 'Strategi pemasaran',
                'prompt' => 'Bantu saya menyusun strategi pemasaran untuk usaha saya. Tanyakan maksimal dua hal penting tentang pelanggan sasaran, lokasi, menu unggulan, tujuan, atau anggaran jika belum tersedia. Buat rencana 7 hari yang realistis, langkah yang bisa dilakukan, ide konten, dan ukuran hasil. Jangan mengarang data usaha atau promo.',
            ],
            [
                'title' => 'Buat caption',
                'prompt' => 'Bantu saya menulis tiga pilihan caption media sosial untuk usaha kopi saya. Tanyakan nama menu atau promo, platform, dan gaya bahasa jika belum ada. Buat caption yang menarik, singkat, memiliki ajakan yang jelas, dan jangan mengarang harga, promo, lokasi, atau fakta.',
            ],
            [
                'title' => 'Susun strategi harga',
                'prompt' => 'Bantu saya menentukan strategi harga menu. Tanyakan biaya bahan, kemasan, biaya lain per porsi, dan target laba jika belum saya berikan. Jelaskan rumus harga dengan bahasa sederhana, lalu beri rekomendasi berdasarkan data. Jangan memberi harga pasti tanpa data biaya; tandai angka contoh sebagai simulasi.',
            ],
            [
                'title' => 'Susun menu',
                'prompt' => 'Bantu saya menyusun menu kafe atau roastery. Pertimbangkan pelanggan sasaran, alat, bahan, waktu penyajian, biaya, dan rentang harga. Tanyakan maksimal dua hal penting jika data belum tersedia, lalu berikan susunan menu awal dan alasan singkat. Jangan menjanjikan menu pasti laku.',
            ],
            [
                'title' => 'Buat naskah video edukasi',
                'prompt' => 'Bantu saya membuat naskah video edukasi kopi berdurasi 30 sampai 45 detik. Tanyakan topik dan penonton sasaran jika belum jelas. Susun pembuka, urutan adegan, narasi, teks layar, dan ajakan di akhir. Gunakan bahasa sederhana dan jangan menyebut klaim teknis yang belum terverifikasi sebagai fakta.',
            ],
        ];
    }

    /**
     * @return array<int, array{category: string, title: string, image_url: ?string, prompt_text: string}>
     */
    public function all(): array
    {
        return [
            [
                'category' => 'FOTO PRODUK & VISUAL',
                'title' => 'Foto kopi segar dengan cahaya alami',
                'image_url' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Enhance this coffee product photo with a bright, soft pastel aesthetic, natural daylight, a clean wooden surface, and delicate condensation droplets on the glass for a fresh and refreshing look.',
            ],
            [
                'category' => 'FOTO PRODUK & VISUAL',
                'title' => 'Suasana hangat untuk kopi andalan',
                'image_url' => 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Transform this coffee product photo into a warm, cozy atmosphere with gentle steam rising from the cup, soft diffused side lighting, and rich, inviting textures.',
            ],
            [
                'category' => 'FOTO PRODUK & VISUAL',
                'title' => 'Foto kopi dengan nuansa sinematik',
                'image_url' => 'https://images.unsplash.com/photo-1595928640742-38314ff36a28?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Give this coffee product photo a dramatic, dark, moody aesthetic with deep shadows, subtle vapor near the surface, and a sleek dark wooden background. Keep the drink realistic and appetizing.',
            ],
            [
                'category' => 'FOTO PRODUK & VISUAL',
                'title' => 'Foto produk minimalis dan cerah',
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Refine this coffee product photo with a clean minimalist style, bright high-key lighting, sharp details on the crema, and a smooth light-toned wooden backdrop.',
            ],
            [
                'category' => 'FOTO PRODUK & VISUAL',
                'title' => 'Foto kopi dengan cahaya senja',
                'image_url' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Enhance this coffee product photo with a cinematic golden-hour glow, warm amber highlights, subtle floating dust motes, and a rich rustic wooden texture.',
            ],
            [
                'category' => 'PROMO & DISKON',
                'title' => 'Poster promo diskon 10%',
                'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Design an eye-catching promotional poster featuring an iced coffee on a warm wooden background. Highlight a 10% discount with clear, legible typography, soft lighting, and a modern commercial style.',
            ],
            [
                'category' => 'PROMO & DISKON',
                'title' => 'Promo beli dua dengan visual ceria',
                'image_url' => 'https://images.unsplash.com/photo-1507133754945-2f9f8c3258c7?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create a vibrant coffee promotion showing two appealing cups side by side on a rustic wooden table. Leave clear space for the offer and use bright, cheerful, warm lighting.',
            ],
            [
                'category' => 'PROMO & DISKON',
                'title' => 'Banner promo akhir pekan',
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create an elegant weekend coffee promotion featuring an espresso cup and coffee beans on a dark wooden surface. Use a minimalist layout with a prominent, easy-to-read offer area.',
            ],
            [
                'category' => 'PROMO & DISKON',
                'title' => 'Flyer promo kopi dan pastry',
                'image_url' => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Design a coffee flash-sale flyer with steaming coffee and a pastry on a wooden table. Use a tasteful discount badge, clear hierarchy, and a clean, inviting aesthetic.',
            ],
            [
                'category' => 'PROMO & DISKON',
                'title' => 'Visual grand opening kedai',
                'image_url' => 'https://images.unsplash.com/photo-1512568400610-62da28bc8a13?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create a celebratory grand-opening advertisement featuring a signature coffee cup on a warm wooden tray, cozy lighting, and a clear headline area for the opening offer.',
            ],
            [
                'category' => 'LOGO & BRANDING',
                'title' => 'Konsep logo kedai minimalis',
                'image_url' => null,
                'prompt_text' => 'Design a modern, clean, minimalist coffee-shop logo featuring a subtle coffee-bean or cup symbol, balanced geometric typography, and a professional vector identity on a plain background.',
            ],
            [
                'category' => 'LOGO & BRANDING',
                'title' => 'Mockup kemasan cup dan stiker',
                'image_url' => null,
                'prompt_text' => 'Create a commercial product mockup of an iced-coffee takeaway cup and kraft-paper coffee bag with a cohesive cafe-brand sticker. Use realistic packaging textures and clean studio lighting.',
            ],
            [
                'category' => 'LOGO & BRANDING',
                'title' => 'Konsep plang dan signage kedai',
                'image_url' => null,
                'prompt_text' => 'Create a realistic storefront concept for a cozy local coffee shop with a distinctive hanging sign and window branding, inviting atmosphere, natural daylight, and locally grounded character.',
            ],
            [
                'category' => 'KONTEN VIDEO',
                'title' => 'Video pendek kopi dengan uap',
                'image_url' => 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Animate this coffee photo with gentle realistic steam rising from the cup, subtle warm cafe lighting, and a slow cinematic camera push-in suitable for a short social-media promotion.',
            ],
            [
                'category' => 'KONTEN VIDEO',
                'title' => 'Video makro biji kopi roasted',
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create a cinematic macro video of freshly roasted coffee beans tumbling onto a warm wooden counter, with a golden spotlight, rich textures, and smooth slow motion.',
            ],
            [
                'category' => 'KONTEN VIDEO',
                'title' => 'Video edukasi proses menggiling kopi',
                'image_url' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create an educational cinematic video showing a barista grinding fresh coffee beans, with warm cafe lighting, slow motion, shallow depth of field, and clear focus on the brewing process.',
            ],
            [
                'category' => 'KONTEN VIDEO',
                'title' => 'Video edukasi seduh manual',
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create an artisanal V60 coffee-brewing video. Show hot water pouring steadily over blooming coffee grounds, a warm wooden bar, soft ambient lighting, and realistic brewing details.',
            ],
            [
                'category' => 'KONTEN VIDEO',
                'title' => 'Video latte art',
                'image_url' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Create a smooth slow-motion video of a barista pouring textured milk into espresso to form latte art, with a warm cozy cafe aesthetic and cinematic close-up framing.',
            ],
            [
                'category' => 'COPYWRITING & CAPTION',
                'title' => 'Caption peluncuran menu baru',
                'image_url' => null,
                'prompt_text' => 'Tulis caption Instagram yang hangat untuk peluncuran menu kopi baru. Buat tiga pilihan dengan gaya berbeda, ajakan berkunjung, dan tagar yang sesuai. Jangan mengarang nama menu, rasa, harga, atau lokasi yang belum saya berikan.',
            ],
            [
                'category' => 'COPYWRITING & CAPTION',
                'title' => 'Caption promo jam sepi',
                'image_url' => null,
                'prompt_text' => 'Tulis caption promosi untuk menarik pelanggan pada jam yang sepi. Buat penawaran, waktu, syarat, dan ajakan mudah dipahami. Tanyakan detail promo jika belum diberikan dan jangan mengarang harga atau ketentuannya.',
            ],
            [
                'category' => 'STRATEGI BISNIS',
                'title' => 'Ide meningkatkan pelanggan kembali',
                'image_url' => null,
                'prompt_text' => 'Berikan tiga cara praktis agar pelanggan yang baru pertama datang mau kembali ke kedai. Sertakan langkah awal dan cara sederhana untuk mengukur hasilnya. Sesuaikan dengan informasi usaha yang saya berikan.',
            ],
            [
                'category' => 'STRATEGI BISNIS',
                'title' => 'Ide kolaborasi usaha lokal',
                'image_url' => null,
                'prompt_text' => 'Berikan tiga ide kolaborasi yang realistis antara kedai kopi lokal dan usaha sekitar. Jelaskan manfaat bagi kedua pihak, langkah untuk memulai, dan cara sederhana mengukur hasilnya. Jangan mengarang nama usaha atau data lokasi.',
            ],
            [
                'category' => 'STRATEGI PEMASARAN',
                'title' => 'Rencana pemasaran 7 hari',
                'image_url' => null,
                'prompt_text' => 'Bantu saya menyusun rencana pemasaran kedai kopi selama 7 hari. Tanyakan maksimal dua hal penting jika data usaha, pelanggan sasaran, menu unggulan, tujuan, atau anggaran belum tersedia. Buat langkah harian yang sederhana, ide konten, ajakan pelanggan, dan ukuran hasil. Jangan mengarang harga, promo, atau data usaha.',
            ],
            [
                'category' => 'STRATEGI HARGA',
                'title' => 'Menentukan harga jual dari biaya',
                'image_url' => null,
                'prompt_text' => 'Bantu saya menghitung saran harga jual minuman. Minta biaya bahan, kemasan, biaya lain per porsi, dan target laba jika belum saya berikan. Tunjukkan rumus dan perhitungannya dengan bahasa sederhana. Jangan memberi harga pasti tanpa data biaya; tandai contoh angka sebagai simulasi.',
            ],
            [
                'category' => 'STRATEGI HARGA',
                'title' => 'Mengevaluasi harga dan paket menu',
                'image_url' => null,
                'prompt_text' => 'Bantu saya mengevaluasi harga menu dan ide paket bundling. Gunakan harga, biaya, jumlah terjual, dan informasi pesaing hanya jika saya berikan. Jelaskan manfaat, risiko, dan cara mencoba paket dalam waktu terbatas. Jangan mengarang data atau menjanjikan keuntungan.',
            ],
            [
                'category' => 'PENYUSUNAN MENU',
                'title' => 'Menyusun menu yang seimbang',
                'image_url' => null,
                'prompt_text' => 'Bantu saya menyusun menu kedai kopi yang ringkas dan seimbang. Pertimbangkan pelanggan sasaran, alat yang tersedia, bahan, waktu penyajian, biaya, dan harga. Jika data belum ada, tanyakan maksimal dua hal lalu berikan kerangka menu awal dengan asumsi yang disebutkan.',
            ],
            [
                'category' => 'PENYUSUNAN MENU',
                'title' => 'Ide menu baru sesuai pelanggan',
                'image_url' => null,
                'prompt_text' => 'Buat beberapa ide menu baru untuk kedai kopi saya berdasarkan pelanggan sasaran dan bahan yang tersedia. Untuk tiap ide, jelaskan rasa utama, bahan, tingkat kerumitan, dan cara menguji minat pelanggan. Jangan menganggap ide tersebut pasti laku.',
            ],
            [
                'category' => 'VIDEO EDUKASI',
                'title' => 'Naskah video edukasi proses seduh',
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Buat konsep video edukasi kopi berdurasi 30 sampai 45 detik tentang langkah dasar menyeduh kopi manual. Sertakan pembuka yang menarik, urutan adegan, narasi singkat, teks layar, dan ajakan di akhir. Gunakan bahasa Indonesia sederhana dan hindari klaim teknis yang belum dipastikan.',
            ],
            [
                'category' => 'VIDEO EDUKASI',
                'title' => 'Naskah video tips menyimpan kopi',
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=900&q=80',
                'prompt_text' => 'Buat konsep video edukasi 30 sampai 45 detik tentang cara menyimpan biji kopi agar kualitasnya terjaga. Sertakan pembuka, tiga adegan, narasi, teks layar, dan ajakan di akhir. Sampaikan tips yang umum dan aman, serta jangan membuat klaim ilmiah yang tidak diketahui.',
            ],
        ];
    }
}
