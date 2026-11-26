<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_page_offers_photo_position_and_zoom_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/profile-settings')
            ->assertOk()
            ->assertSee('admin-profile-photo-crop')
            ->assertSee('admin-profile-photo-zoom')
            ->assertSee('Geser foto untuk memilih bagian yang ingin ditampilkan')
            ->assertSee('256 × 256 piksel');
    }

    public function test_admin_profile_photo_is_saved_through_a_direct_form_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::fake('public');

        $response = $this->actingAs($admin)
            ->from('/admin/profile-settings')
            ->patch(route('admin.profile-photo.update'), [
                'profile_photo' => $this->fakeProfilePhoto(),
            ]);

        $response
            ->assertRedirect('/admin/profile-settings')
            ->assertSessionHas('profile_photo_status', 'Foto profil berhasil diperbarui.');

        $photoPath = $admin->fresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/'.$admin->id.'/', $photoPath);
        Storage::disk('public')->assertExists($photoPath);
        $this->assertSame(Storage::disk('public')->url($photoPath), $admin->fresh()->getFilamentAvatarUrl());
    }

    public function test_customer_cannot_use_the_admin_profile_photo_upload(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        Storage::fake('public');

        $this->actingAs($customer)
            ->patch(route('admin.profile-photo.update'), [
                'profile_photo' => $this->fakeProfilePhoto(),
            ])
            ->assertForbidden();

        $this->assertNull($customer->fresh()->profile_photo_path);
    }

    public function test_invalid_admin_profile_photo_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::fake('public');

        $this->actingAs($admin)
            ->from('/admin/profile-settings')
            ->patch(route('admin.profile-photo.update'), [
                'profile_photo' => UploadedFile::fake()->create('profile.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('profile_photo');

        $this->assertNull($admin->fresh()->profile_photo_path);
    }

    private function fakeProfilePhoto(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'profile.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=')
        );
    }
}
