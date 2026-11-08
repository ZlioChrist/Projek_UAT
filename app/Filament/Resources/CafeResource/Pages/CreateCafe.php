<?php

namespace App\Filament\Resources\CafeResource\Pages;

use App\Support\AuthenticatedUser;
use App\Filament\Resources\CafeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCafe extends CreateRecord
{
    protected static string $resource = CafeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (AuthenticatedUser::get()?->role === 'admin') {
            $data['owner_id'] = AuthenticatedUser::id();
            $data['status'] = 'review';
        }

        return $data;
    }
}
