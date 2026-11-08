<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Support\AuthenticatedUser;
use App\Filament\Resources\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['cafe_id'] = $this->record->cafe_id;

        if (AuthenticatedUser::get()?->role === 'admin') {
            abort_unless(AuthenticatedUser::require()->cafes()->whereKey($this->record->cafe_id)->exists(), 403);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
