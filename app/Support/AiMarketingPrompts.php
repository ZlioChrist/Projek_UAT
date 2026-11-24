<?php

namespace App\Support;

class AiMarketingPrompts
{
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
                'prompt_text' => 'Write a warm, engaging Instagram caption in Indonesian for a new signature coffee at a cozy local cafe. Describe its aroma and flavor, include a friendly local greeting, a clear invitation to visit, and relevant hashtags.',
            ],
            [
                'category' => 'COPYWRITING & CAPTION',
                'title' => 'Caption promo jam sepi',
                'image_url' => null,
                'prompt_text' => 'Write a persuasive but friendly Indonesian caption for an afternoon coffee promotion. Make the offer, time, terms, and call to action easy to understand; avoid inventing details that were not provided.',
            ],
            [
                'category' => 'STRATEGI BISNIS',
                'title' => 'Ide meningkatkan pelanggan kembali',
                'image_url' => null,
                'prompt_text' => 'Act as a coffee-shop business consultant. Suggest three practical ways to encourage first-time visitors to return, using community events, a simple loyalty program, and personal customer experiences. Include steps and ways to measure results.',
            ],
            [
                'category' => 'STRATEGI BISNIS',
                'title' => 'Ide kolaborasi usaha lokal',
                'image_url' => null,
                'prompt_text' => 'Suggest three realistic collaboration ideas between a local independent coffee shop and nearby businesses such as bakeries, bookstores, or clothing shops. Explain the mutual value, a first step, and a simple success measure.',
            ],
        ];
    }
}
