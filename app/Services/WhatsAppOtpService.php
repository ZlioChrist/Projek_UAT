<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class WhatsAppOtpService
{
    private const OTP_TTL_MINUTES = 5;

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function send(string $purpose, string $phone, User $user): void
    {
        $phoneKey = hash('sha256', $phone);
        $sendKey = "whatsapp-otp:send:{$purpose}:{$phoneKey}";

        if (RateLimiter::tooManyAttempts($sendKey, 1)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Tunggu satu menit sebelum meminta kode baru.',
            ]);
        }

        RateLimiter::hit($sendKey, 60);
        $code = (string) random_int(100000, 999999);
        $cacheKey = $this->challengeKey($purpose, $phone);

        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        try {
            app(MetaWhatsAppOtpSender::class)->send($phone, $code);
        } catch (ConnectionException|\Illuminate\Http\Client\RequestException|\RuntimeException $exception) {
            Cache::forget($cacheKey);

            throw $exception;
        }
    }

    public function verify(string $purpose, string $phone, string $code, int $userId): bool
    {
        $phoneKey = hash('sha256', $phone);
        $attemptKey = "whatsapp-otp:verify:{$purpose}:{$phoneKey}";
        $cacheKey = $this->challengeKey($purpose, $phone);
        $challenge = Cache::get($cacheKey);

        if (
            RateLimiter::tooManyAttempts($attemptKey, self::MAX_VERIFY_ATTEMPTS)
            || ! is_array($challenge)
            || (int) ($challenge['user_id'] ?? 0) !== $userId
        ) {
            return false;
        }

        if (! Hash::check($code, (string) ($challenge['code_hash'] ?? ''))) {
            RateLimiter::hit($attemptKey, self::OTP_TTL_MINUTES * 60);
            if (RateLimiter::tooManyAttempts($attemptKey, self::MAX_VERIFY_ATTEMPTS)) {
                Cache::forget($cacheKey);
            }

            return false;
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($attemptKey);
        RateLimiter::clear("whatsapp-otp:send:{$purpose}:{$phoneKey}");

        return true;
    }

    private function challengeKey(string $purpose, string $phone): string
    {
        return 'whatsapp-otp:challenge:'.hash('sha256', $purpose.'|'.$phone);
    }
}
