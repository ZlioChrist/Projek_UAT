<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserBusinessStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_shows_business_statuses_next_to_roles(): void
    {
        $developer = User::factory()->create(['role' => 'developer']);
        $cafeOwner = User::factory()->create(['name' => 'Pemilik Kafe', 'role' => 'admin']);
        $roasteryOwner = User::factory()->create(['name' => 'Pemilik Roastery', 'role' => 'roastery']);
        $visitor = User::factory()->create(['name' => 'Pengunjung', 'role' => 'user']);

        Cafe::factory()->create(['owner_id' => $cafeOwner->id, 'status' => 'draft']);
        Roastery::factory()->create(['owner_id' => $roasteryOwner->id, 'status' => 'review']);

        $this->actingAs($developer)
            ->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Peran')
            ->assertSee('Status')
            ->assertSee('Draf')
            ->assertSee('Pending')
            ->assertSee('Tidak ada usaha');
    }

    public function test_non_developers_cannot_open_user_management(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_developer_can_edit_business_status_without_selecting_the_business_again(): void
    {
        $developer = User::factory()->create(['role' => 'developer']);
        $owner = User::factory()->create(['role' => 'admin']);
        $firstCafe = Cafe::factory()->create(['owner_id' => $owner->id, 'status' => 'draft']);
        $secondCafe = Cafe::factory()->create(['owner_id' => $owner->id, 'status' => 'review']);

        $this->actingAs($developer);

        $editForm = Livewire::test(EditUser::class, ['record' => $owner->getRouteKey()])
            ->assertFormFieldDoesNotExist('business_to_update')
            ->assertFormFieldExists('business_name_cafe_'.$firstCafe->id, fn (TextInput $field): bool => $field->getLabel() === 'Nama kafe')
            ->assertFormFieldExists('business_status_cafe_'.$firstCafe->id, fn (Select $field): bool => $field->getOptions() === [
                'published' => 'Publik',
                'review' => 'Pending',
                'draft' => 'Draf',
            ])
            ->assertFormFieldExists('business_status_cafe_'.$secondCafe->id)
            ->assertSee('Publik')
            ->assertSee('Pending')
            ->assertSee('Draf')
            ->assertSet('data.business_name_cafe_'.$firstCafe->id, $firstCafe->name)
            ->assertSet('data.business_name_cafe_'.$secondCafe->id, $secondCafe->name)
            ->assertSet('data.business_status_cafe_'.$firstCafe->id, 'draft')
            ->assertSet('data.business_status_cafe_'.$secondCafe->id, 'review')
            ->fillForm([
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => $owner->role,
                'business_name_cafe_'.$firstCafe->id => $firstCafe->name,
                'business_name_cafe_'.$secondCafe->id => 'Nama Kafe Baru',
                'business_status_cafe_'.$secondCafe->id => 'published',
            ]);

        $this->assertSame('published', $editForm->get('data.business_status_cafe_'.$secondCafe->id));
        $editForm->call('save');
        $this->assertSame([], $editForm->errors()->toArray());

        $this->assertDatabaseHas('cafes', ['id' => $firstCafe->id, 'name' => $firstCafe->name, 'status' => 'draft']);
        $this->assertDatabaseHas('cafes', ['id' => $secondCafe->id, 'name' => 'Nama Kafe Baru', 'status' => 'published']);
    }
}
