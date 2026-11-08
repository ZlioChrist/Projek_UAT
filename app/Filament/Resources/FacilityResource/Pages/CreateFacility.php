<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Support\AuthenticatedUser;
use App\Filament\Resources\FacilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFacility extends CreateRecord
{
    protected static string $resource = FacilityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (AuthenticatedUser::get()?->role === 'admin') {
            $data['cafe_id'] = AuthenticatedUser::require()->cafes()->whereKey($data['cafe_id'] ?? 0)->value('id');
            abort_unless($data['cafe_id'], 403);
        }

        return $data;
    }
}
