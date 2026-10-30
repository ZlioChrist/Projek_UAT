<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\CustomerFeedback;
use App\Models\Roastery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerFeedbackController extends Controller
{
    public function create(): View
    {
        $shops = Cafe::query()->where('status', 'published')->orderBy('name')->get(['id', 'name'])
            ->map(fn (Cafe $shop) => ['value' => 'cafe:'.$shop->id, 'name' => $shop->name.' (Kafe)'])
            ->concat(
                Roastery::query()->where('status', 'published')->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Roastery $shop) => ['value' => 'roastery:'.$shop->id, 'name' => $shop->name.' (Roastery)'])
            );

        return view('feedback.create', compact('shops'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop' => ['required', 'regex:/^(cafe|roastery):[0-9]+$/'],
            'category' => ['required', 'in:complaint,suggestion,criticism,praise,other'],
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_contact' => ['required', 'string', 'min:5', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        [$type, $id] = explode(':', $validated['shop'], 2);
        $shop = $type === 'cafe'
            ? Cafe::query()->where('status', 'published')->findOrFail($id)
            : Roastery::query()->where('status', 'published')->findOrFail($id);

        CustomerFeedback::create([
            'cafe_id' => $shop instanceof Cafe ? $shop->id : null,
            'roastery_id' => $shop instanceof Roastery ? $shop->id : null,
            'category' => $validated['category'],
            'customer_name' => trim($validated['customer_name']),
            'customer_contact' => trim($validated['customer_contact']),
            'message' => trim($validated['message']),
        ]);

        return redirect()->route('feedback.create')->with('status', 'Terima kasih. Masukan Anda sudah tersimpan untuk ditindaklanjuti.');
    }
}
