<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    public function edit(Request $request): \Illuminate\View\View
    {
        $profilePhotoUrl = $request->user()->profile_photo_path
            ? Storage::disk('public')->url($request->user()->profile_photo_path)
            : null;

        return view('account.profile-edit', compact('profilePhotoUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ]);

        $oldPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->storePublicly(
                'profile-photos/'.$user->id,
                'public'
            );
            if (! $newPhotoPath) {
                return back()->withErrors([
                    'profile_photo' => 'Foto profil gagal disimpan. Silakan coba lagi.',
                ])->withInput();
            }
        }

        $user->name = trim($validated['name']);
        $user->email = strtolower($validated['email']);
        if ($newPhotoPath !== null) {
            $user->profile_photo_path = $newPhotoPath;
        } elseif ($request->boolean('remove_profile_photo')) {
            $user->profile_photo_path = null;
        }
        $user->save();

        $ownedPhotoDirectory = 'profile-photos/'.$user->id.'/';
        if (
            $oldPhotoPath
            && str_starts_with($oldPhotoPath, $ownedPhotoDirectory)
            && ($newPhotoPath !== null || $request->boolean('remove_profile_photo'))
            && ! Storage::disk('public')->delete($oldPhotoPath)
        ) {
            Log::warning('Unable to remove replaced customer profile photo.', [
                'user_id' => $user->id,
                'path' => $oldPhotoPath,
            ]);
        }

        return redirect()->route('account.profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }
}
