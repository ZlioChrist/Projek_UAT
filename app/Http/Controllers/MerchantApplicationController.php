<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\City;
use App\Models\MerchantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless(UserRole::fromUserValue($request->user()->role) === UserRole::User, 403);

        $application = $request->user()->merchantApplications()->latest()->first();
        if ($application?->status === MerchantApplication::STATUS_PENDING) {
            return redirect()->route('account')->with('status', 'Pengajuan Anda sedang ditinjau oleh tim WadahNgopi.');
        }

        $cities = City::query()->orderBy('name')->get(['id', 'name']);

        return view('account.merchant-application', compact('application', 'cities'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(UserRole::fromUserValue($request->user()->role) === UserRole::User, 403);

        if ($request->user()->merchantApplications()->where('status', MerchantApplication::STATUS_PENDING)->exists()) {
            return redirect()->route('account')->with('status', 'Pengajuan Anda masih menunggu pemeriksaan.');
        }

        $validated = $request->validate([
            'business_type' => ['required', 'in:cafe,roastery'],
            'business_name' => ['required', 'string', 'min:2', 'max:180'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'min:8', 'max:2000'],
            'whatsapp_number' => ['required', 'string', 'regex:/^[0-9+() .-]+$/', 'min:8', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->merchantApplications()->create([
            ...$validated,
            'business_name' => trim($validated['business_name']),
            'address' => trim($validated['address']),
            'whatsapp_number' => preg_replace('/\D+/', '', $validated['whatsapp_number']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'status' => MerchantApplication::STATUS_PENDING,
        ]);

        return redirect()->route('account')->with('status', 'Pengajuan kafe/roastery berhasil dikirim dan sedang menunggu pemeriksaan.');
    }
}
