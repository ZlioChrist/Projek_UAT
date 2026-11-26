<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminProfilePhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = UserRole::fromUserValue($user?->role);

        abort_unless(in_array($role, [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true), 403);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldPhotoPath = $user->profile_photo_path;
        $newPhotoPath = $validated['profile_photo']->storePublicly(
            'profile-photos/'.$user->id,
            'public'
        );

        if (! $newPhotoPath) {
            return back()->withErrors([
                'profile_photo' => 'Foto profil gagal disimpan. Silakan coba lagi.',
            ]);
        }

        $user->profile_photo_path = $newPhotoPath;
        $user->save();

        $ownedPhotoDirectory = 'profile-photos/'.$user->id.'/';
        if (
            $oldPhotoPath
            && str_starts_with($oldPhotoPath, $ownedPhotoDirectory)
            && $oldPhotoPath !== $newPhotoPath
            && ! Storage::disk('public')->delete($oldPhotoPath)
        ) {
            Log::warning('Unable to remove replaced admin profile photo.', [
                'user_id' => $user->id,
                'path' => $oldPhotoPath,
            ]);
        }

        return back()->with('profile_photo_status', 'Foto profil berhasil diperbarui.');
    }
}
