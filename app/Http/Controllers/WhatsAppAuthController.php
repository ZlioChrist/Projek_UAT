<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use App\Support\WhatsAppPhoneNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class WhatsAppAuthController extends Controller
{
    public function requestLoginCode(Request $request, string $audience, WhatsAppOtpService $otp): RedirectResponse
    {
        abort_unless(in_array($audience, ['customer', 'admin'], true), 404);
        $validated = $request->validate(['phone_number' => ['required', 'string', 'max:32']]);
        $phone = WhatsAppPhoneNumber::normalize($validated['phone_number']);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone_number' => 'Masukkan nomor WhatsApp Indonesia yang valid.',
            ]);
        }

        $user = User::query()
            ->where('phone_number', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();

        if ($user && $this->userMatchesAudience($user, $audience)) {
            try {
                $otp->send('login:'.$audience, $phone, $user);
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (ConnectionException|RequestException|RuntimeException $exception) {
                Log::warning('WhatsApp login OTP could not be sent.', [
                    'exception' => $exception::class,
                ]);

                return back()->withErrors([
                    'phone_number' => 'Kode WhatsApp gagal dikirim. Periksa konfigurasi layanan dan coba lagi.',
                ])->withInput();
            }
        } else {
            $user = null;
        }

        $request->session()->put('auth.whatsapp.login', [
            'audience' => $audience,
            'phone' => $phone,
            'user_id' => $user?->id,
        ]);

        return back()->with('whatsapp_login_sent', true);
    }

    public function verifyLoginCode(Request $request, WhatsAppOtpService $otp): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits:6'],
        ]);
        $phone = WhatsAppPhoneNumber::normalize($validated['phone_number']);
        $pending = $request->session()->get('auth.whatsapp.login');

        if (
            ! $phone
            || ! is_array($pending)
            || ! hash_equals((string) ($pending['phone'] ?? ''), $phone)
            || ! in_array($pending['audience'] ?? null, ['customer', 'admin'], true)
            || ! is_numeric($pending['user_id'] ?? null)
        ) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        $audience = $pending['audience'];
        $user = User::query()->find((int) $pending['user_id']);
        if (
            ! $user
            || $user->phone_number !== $phone
            || ! $user->phone_verified_at
            || ! $this->userMatchesAudience($user, $audience)
            || ! $otp->verify('login:'.$audience, $phone, $validated['code'], $user->id)
        ) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        $request->session()->forget('auth.whatsapp.login');
        Auth::guard($audience === 'admin' ? 'web' : 'customer')->login($user);
        $request->session()->regenerate();

        return redirect($audience === 'admin' ? '/admin' : route('account'));
    }

    public function showPhoneLink(Request $request, string $audience): View
    {
        $user = $this->authorizedUser($request, $audience);

        return view('auth.whatsapp-phone-link', [
            'audience' => $audience,
            'phoneNumber' => $user->phone_number,
            'verifiedAt' => $user->phone_verified_at,
        ]);
    }

    public function requestPhoneLinkCode(Request $request, string $audience, WhatsAppOtpService $otp): RedirectResponse
    {
        $user = $this->authorizedUser($request, $audience);
        $validated = $request->validate(['phone_number' => ['required', 'string', 'max:32']]);
        $phone = WhatsAppPhoneNumber::normalize($validated['phone_number']);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone_number' => 'Masukkan nomor WhatsApp Indonesia yang valid.',
            ]);
        }

        if (User::query()->where('phone_number', $phone)->where('id', '<>', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => 'Nomor ini sudah ditautkan ke akun lain.',
            ]);
        }

        try {
            $otp->send('link:'.$audience.':'.$user->id, $phone, $user);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Log::warning('WhatsApp phone verification OTP could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'phone_number' => 'Kode WhatsApp gagal dikirim. Periksa konfigurasi layanan dan coba lagi.',
            ])->withInput();
        }

        $request->session()->put('auth.whatsapp.link', [
            'audience' => $audience,
            'phone' => $phone,
        ]);

        return back()->with('whatsapp_link_sent', true);
    }

    public function verifyPhoneLinkCode(Request $request, string $audience, WhatsAppOtpService $otp): RedirectResponse
    {
        $user = $this->authorizedUser($request, $audience);
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits:6'],
        ]);
        $phone = WhatsAppPhoneNumber::normalize($validated['phone_number']);
        $pending = $request->session()->get('auth.whatsapp.link');

        if (
            ! $phone
            || ! is_array($pending)
            || ($pending['audience'] ?? null) !== $audience
            || ! hash_equals((string) ($pending['phone'] ?? ''), $phone)
            || ! $otp->verify('link:'.$audience.':'.$user->id, $phone, $validated['code'], $user->id)
        ) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        if (User::query()->where('phone_number', $phone)->where('id', '<>', $user->id)->exists()) {
            return back()->withErrors(['phone_number' => 'Nomor ini sudah ditautkan ke akun lain.']);
        }

        $user->phone_number = $phone;
        $user->phone_verified_at = now();
        $user->save();
        $request->session()->forget('auth.whatsapp.link');

        return back()->with('status', 'Nomor WhatsApp berhasil diverifikasi dan ditautkan.');
    }

    private function authorizedUser(Request $request, string $audience): User
    {
        abort_unless(in_array($audience, ['customer', 'admin'], true), 404);

        $guard = $audience === 'admin' ? 'web' : 'customer';
        $user = Auth::guard($guard)->user();
        abort_unless($user instanceof User && $this->userMatchesAudience($user, $audience), 403);

        return $user;
    }

    private function userMatchesAudience(User $user, string $audience): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $audience === 'customer'
            ? $role === UserRole::User
            : in_array($role, [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true);
    }
}
