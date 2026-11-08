<?php

namespace App\Filament\Resources\RoasteryResource\Pages;

use App\Support\AuthenticatedUser;
use App\Filament\Resources\RoasteryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoastery extends EditRecord
{
    protected static string $resource = RoasteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (AuthenticatedUser::get()?->role === 'roastery') {
            $data['owner_id'] = AuthenticatedUser::id();
            $data['status'] = 'review';
        }

        return $data;
    }
}
