<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaWhatsAppOtpSender
{
    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function send(string $recipient, string $code): void
    {
        $accessToken = config('services.meta_whatsapp.access_token');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');
        $template = config('services.meta_whatsapp.otp_template');
        $graphVersion = config('services.meta_whatsapp.graph_version');

        if (! filled($accessToken) || ! filled($phoneNumberId) || ! filled($template)) {
            throw new RuntimeException('Meta WhatsApp Cloud API belum dikonfigurasi.');
        }

        if (! ctype_digit((string) $phoneNumberId) || strlen((string) $phoneNumberId) < 10) {
            throw new RuntimeException('META_WHATSAPP_PHONE_NUMBER_ID harus berupa ID dari Meta, bukan nomor telepon.');
        }

        if (! preg_match('/^v[0-9]{2}\.[0-9]+$/', (string) $graphVersion)) {
            throw new RuntimeException('Versi Graph API Meta tidak valid.');
        }

        Http::acceptJson()
            ->withToken($accessToken)
            ->connectTimeout(5)
            ->timeout(15)
            ->post("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'id'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();
    }
}
