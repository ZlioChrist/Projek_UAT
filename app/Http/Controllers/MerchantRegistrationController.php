<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MerchantRegistrationController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect('/admin/login?mode=register');
    }

    public function store(Request $request): RedirectResponse
    {
        $newCity = $request->input('city_id') === 'other';
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_type' => ['required', 'in:cafe,roastery'],
            'business_name' => ['required', 'string', 'min:2', 'max:180'],
            'address' => ['required', 'string', 'min:8', 'max:2000'],
            'whatsapp_number' => ['required', 'string', 'regex:/^[0-9+() .-]+$/', 'min:8', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
        $rules['city_id'] = $newCity
            ? ['required', 'in:other']
            : [
                'required',
                'integer',
                'exists:cities,id',
                function ($attribute, $value, $fail): void {
                    $isUatCity = City::query()
                        ->whereKey($value)
                        ->where(function ($query): void {
                            $query->where('slug', 'kota-simulasi-uat')
                                ->orWhereRaw('LOWER(name) = ?', ['kota simulasi uat']);
                        })
                        ->exists();

                    if ($isUatCity) {
                        $fail('Kota Simulasi UAT tidak dapat dipilih.');
                    }
                },
            ];
        $rules['city_name'] = $newCity
            ? [
                'required',
                'string',
                'min:2',
                'max:120',
                function ($attribute, $value, $fail): void {
                    if (Str::slug(trim($value)) === 'kota-simulasi-uat') {
                        $fail('Nama kota tersebut tidak dapat digunakan.');
                    }
                },
            ]
            : ['nullable', 'string', 'max:120'];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect('/admin/login?mode=register')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated): void {
            if ($validated['city_id'] === 'other') {
                $cityName = trim($validated['city_name']);
                $citySlug = Str::slug($cityName) ?: 'kota-'.substr(hash('sha256', mb_strtolower($cityName)), 0, 20);
                $cityId = City::query()->firstOrCreate(
                    ['slug' => $citySlug],
                    ['name' => $cityName],
                )->id;
            } else {
                $cityId = (int) $validated['city_id'];
            }

            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'role' => 'user',
            ]);

            $user->merchantApplications()->create([
                'business_type' => $validated['business_type'],
                'business_name' => trim($validated['business_name']),
                'city_id' => $cityId,
                'address' => trim($validated['address']),
                'whatsapp_number' => preg_replace('/\D+/', '', $validated['whatsapp_number']),
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'status' => 'pending',
            ]);
        });

        return redirect()
            ->to('/admin/login?mode=register')
            ->with('status', 'Akun dan pengajuan berhasil dibuat. Tim WadahNgopi akan meninjau usaha Anda sebelum akses pengelola diaktifkan.');
    }
}
