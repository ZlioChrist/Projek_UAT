<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    public function redirect(string $audience): RedirectResponse
    {
        abort_unless(in_array($audience, ['customer', 'admin'], true), 404);
        abort_unless($this->isGoogleConfigured(), 503, 'Login Google belum dikonfigurasi.');

        session(['auth.google.audience' => $audience]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $audience = session()->pull('auth.google.audience');
        if (! in_array($audience, ['customer', 'admin'], true)) {
            return redirect()->route('login')->withErrors([
                'auth' => 'Sesi login Google tidak valid. Silakan mulai kembali.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            return $this->loginPage($audience, 'Sesi Google kedaluwarsa atau tidak valid. Silakan coba lagi.');
        } catch (GuzzleException $exception) {
            Log::warning('Google sign-in failed.', ['exception' => $exception::class]);

            return $this->loginPage($audience, 'Login Google gagal dihubungi. Silakan coba lagi.');
        }

        $rawUser = $googleUser->getRaw();
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());
        $emailVerified = filter_var($rawUser['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        if (! $emailVerified || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $googleId === '') {
            return $this->loginPage($audience, 'Google harus memberikan email terverifikasi untuk login.');
        }

        $user = User::query()->where('google_id', $googleId)->first();
        if ($user && ! $this->userMatchesAudience($user, $audience)) {
            return $this->loginPage($audience, 'Akun Google ini tidak memiliki akses untuk halaman tersebut.');
        }
        if ($user && ! hash_equals(strtolower($user->email), $email)) {
            return $this->loginPage($audience, 'Email Google berubah. Hubungi pengelola untuk memperbarui tautan akun.');
        }

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if ($user && ! $this->userMatchesAudience($user, $audience)) {
                return $this->loginPage($audience, 'Akun Google ini tidak memiliki akses untuk halaman tersebut.');
            }

            if ($user && $user->google_id && ! hash_equals($user->google_id, $googleId)) {
                return $this->loginPage($audience, 'Akun ini sudah ditautkan ke akun Google lain.');
            }

            if (! $user && $audience === 'admin') {
                return $this->loginPage($audience, 'Akun admin harus dibuat terlebih dahulu oleh pengelola.');
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => trim((string) $googleUser->getName()) ?: $email,
                    'email' => $email,
                    'password' => bin2hex(random_bytes(32)),
                    'role' => UserRole::User->value,
                    'google_id' => $googleId,
                ]);
                $user->email_verified_at = now();
                $user->save();
            } else {
                $user->google_id = $googleId;
                $user->email_verified_at ??= now();
                $user->save();
            }
        }

        $guard = $audience === 'admin' ? 'web' : 'customer';
        Auth::guard($guard)->login($user);
        request()->session()->regenerate();

        return redirect($audience === 'admin' ? '/admin' : route('account'));
    }

    private function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function userMatchesAudience(User $user, string $audience): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $audience === 'customer'
            ? $role === UserRole::User
            : in_array($role, [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true);
    }

    private function loginPage(string $audience, string $message): RedirectResponse
    {
        $route = $audience === 'admin' ? '/admin/login' : route('login');

        return redirect($route)->withErrors(['auth' => $message]);
    }
}
