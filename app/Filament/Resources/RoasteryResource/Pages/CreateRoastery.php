<?php

namespace App\Filament\Resources\RoasteryResource\Pages;

use App\Filament\Resources\RoasteryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoastery extends CreateRecord
{
    protected static string $resource = RoasteryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->role === 'roastery') {
            $data['owner_id'] = auth()->id();
            $data['status'] = 'review';
        }

        return $data;
    }
}
