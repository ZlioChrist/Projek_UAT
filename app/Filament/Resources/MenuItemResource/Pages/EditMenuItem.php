<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array(auth()->user()?->role, ['admin', 'roastery'], true)) {
            if ($this->record->cafe_id) {
                abort_unless(auth()->user()?->role === 'admin', 403);
                $data['cafe_id'] = auth()->user()->cafes()->whereKey($this->record->cafe_id)->value('id');
                abort_unless($data['cafe_id'], 403);
                $data['roastery_id'] = null;
            } else {
                $data['roastery_id'] = auth()->user()->roasteries()->whereKey($this->record->roastery_id)->value('id');
                abort_unless($data['roastery_id'], 403);
                $data['cafe_id'] = null;
            }
        }
        abort_if(! empty($data['cafe_id']) === ! empty($data['roastery_id']), 422, 'Pilih tepat satu kedai untuk produk ini.');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->disabled(fn () => (int) $this->record->reserved_stock > 0)];
    }
}
