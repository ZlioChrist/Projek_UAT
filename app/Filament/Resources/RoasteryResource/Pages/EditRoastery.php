<?php

namespace App\Filament\Resources\RoasteryResource\Pages;

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
        if (auth()->user()?->role === 'roastery') {
            $data['owner_id'] = auth()->id();
            $data['status'] = 'review';
        }

        return $data;
    }
}
