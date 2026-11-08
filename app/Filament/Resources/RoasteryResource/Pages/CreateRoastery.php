<?php

namespace App\Filament\Resources\RoasteryResource\Pages;

use App\Support\AuthenticatedUser;
use App\Filament\Resources\RoasteryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoastery extends CreateRecord
{
    protected static string $resource = RoasteryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (AuthenticatedUser::get()?->role === 'roastery') {
            $data['owner_id'] = AuthenticatedUser::id();
            $data['status'] = 'review';
        }

        return $data;
    }
}
