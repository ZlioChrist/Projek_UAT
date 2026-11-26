<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use UnexpectedValueException;

class CloudflareMarketingAssistant
{
    private const SYSTEM_INSTRUCTION =
        'Anda adalah asisten usaha untuk pemilik kafe dan roastery di Indonesia. Ikuti perintah dengan aman dan gunakan riwayat percakapan untuk pertanyaan lanjutan. '
        .'Jadilah ramah, cerdas, profesional, dan hangat. Gunakan bahasa Indonesia sehari-hari yang alami dan mudah dimengerti. Jangan kaku, membosankan, atau terlalu to the point. Berikan konteks, alasan, atau contoh sederhana bila membantu, tetapi tetap fokus dan tidak bertele-tele. Sesuaikan panjang jawaban dengan kebutuhan pengguna dan hindari pembuka yang selalu sama. '
        .'Utamakan maksud pesan terbaru, dan jangan menganggap semua obrolan harus tentang usaha. Jika pengguna menyapa, bertanya kabar, bercanda, atau mengajak ngobrol santai, tanggapi dulu dengan hangat dan alami seperti teman ngobrol. Jawab pertanyaannya, lalu tanyakan balik kabar atau tanggapi topik yang sama. Jangan langsung memberi saran usaha, mempromosikan fitur, atau mengarahkan obrolan ke tujuan bisnis kecuali pengguna memintanya. Variasikan jawaban agar tidak terdengar seperti balasan template. Tetap jujur bahwa Anda adalah AI dan jangan mengaku punya pengalaman atau perasaan manusia. '
        .'Hindari istilah teknis dan bahasa formal yang rumit; jelaskan istilah yang perlu dipakai dengan contoh sederhana. Jangan menggunakan Markdown, tanda bintang, pagar, garis pemisah, tabel, emoji, atau hiasan. Gunakan paragraf pendek dan daftar bernomor bila membantu. '
        .'Jangan mengarang nama usaha, harga, biaya, promo, lokasi, hasil penjualan, pesaing, atau data lain yang belum diberikan. Untuk strategi pemasaran, berikan langkah yang sesuai dengan informasi pengguna, cara mencoba, dan ukuran hasil yang mudah diamati. '
        .'Untuk saran harga, minta biaya bahan, kemasan, dan biaya lain terlebih dahulu; jelaskan rumus dan tandai angka contoh sebagai simulasi, bukan harga pasti. Untuk susunan menu, pertimbangkan pelanggan sasaran, alat, bahan, biaya, dan waktu penyajian; jangan menjanjikan menu pasti laku. '
        .'Untuk caption, buat teks yang siap disunting dan jangan menambahkan promo atau fakta yang tidak diberikan. Untuk naskah video edukasi, berikan pembuka, adegan, narasi, teks layar, dan penutup; jangan menyajikan klaim teknis kopi yang belum terverifikasi sebagai fakta. '
        .'Jika informasi penting kurang, ajukan maksimal dua pertanyaan singkat lalu berikan pilihan awal dengan asumsi yang disebutkan.';

    /**
     * @param  array<int, array{role: string, text: string}>  $messages
     * @param  array{mime_type: string, data: string}|null  $image
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function chat(array $messages, ?array $image = null): string
    {
        if ($messages === []) {
            throw new UnexpectedValueException('A chat message is required.');
        }

        $providerMessages = [
            ['role' => 'system', 'content' => self::SYSTEM_INSTRUCTION],
            ...array_map(
                fn (array $message): array => [
                    'role' => $message['role'],
                    'content' => $message['text'],
                ],
                $messages,
            ),
        ];

        if ($image !== null) {
            $lastMessageIndex = array_key_last($providerMessages);
            if ($lastMessageIndex === null || $providerMessages[$lastMessageIndex]['role'] !== 'user') {
                throw new UnexpectedValueException('An image must be attached to a user message.');
            }

            $providerMessages[$lastMessageIndex]['content'] = [
                [
                    'type' => 'text',
                    'text' => $providerMessages[$lastMessageIndex]['content'],
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:'.$image['mime_type'].';base64,'.$image['data'],
                        'detail' => 'low',
                    ],
                ],
            ];
        }

        return $this->requestText($providerMessages);
    }

    /**
     * @return array{data_uri: string, mime_type: string}
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function generateImage(string $prompt): array
    {
        $response = $this->sendRequest(
            config('services.cloudflare.image_model', '@cf/black-forest-labs/flux-1-schnell'),
            ['prompt' => $prompt, 'steps' => 4],
            120,
        );
        $data = $response->json('result.image');

        if (! is_string($data) || base64_decode($data, true) === false) {
            throw new UnexpectedValueException('Cloudflare Workers AI did not return a valid image.');
        }

        return [
            'data_uri' => 'data:image/jpeg;base64,'.$data,
            'mime_type' => 'image/jpeg',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws RuntimeException
     */
    private function sendRequest(string $model, array $payload, int $timeout = 60): Response
    {
        $accountId = config('services.cloudflare.account_id');
        $apiToken = config('services.cloudflare.api_token');

        if (! is_string($accountId) || trim($accountId) === ''
            || ! is_string($apiToken) || trim($apiToken) === '') {
            throw new RuntimeException('Cloudflare Workers AI credentials are not configured.');
        }

        $response = Http::acceptJson()
            ->withToken($apiToken)
            ->timeout($timeout)
            ->post(
                'https://api.cloudflare.com/client/v4/accounts/'.$accountId.'/ai/run/'.$model,
                $payload,
            );

        $response->throw();

        return $response;
    }

    /**
     * @param  array<int, array{role: string, content: string|array<int, array<string, mixed>>}>  $messages
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    private function requestText(array $messages): string
    {
        $response = $this->sendRequest(
            config('services.cloudflare.chat_model', '@cf/qwen/qwen3.8-27b'),
            [
                'messages' => $messages,
                'max_completion_tokens' => 1200,
                'reasoning_effort' => 'low',
                'chat_template_kwargs' => ['enable_thinking' => false],
            ],
        );
        $text = $response->json('result.choices.0.message.content')
            ?? $response->json('result.response');

        if (! is_string($text) || trim($text) === '') {
            throw new UnexpectedValueException('Cloudflare Workers AI returned an invalid text response.');
        }

        return $this->plainIndonesianText($text);
    }

    private function plainIndonesianText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/```[A-Za-z]*\s*/', '', $text) ?? $text;
        $text = str_replace(['```', '**', '__', '~~'], '', $text);
        $text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $text) ?? $text;
        $text = preg_replace('/^[ \t]*[*+-][ \t]+/m', '- ', $text) ?? $text;
        $text = preg_replace('/[*`]/', '', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
