<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['cafe_id'] = $this->record->cafe_id;
        $data['roastery_id'] = $this->record->roastery_id;

        $role = UserRole::fromUserValue(AuthenticatedUser::get()?->role);

        if (in_array($role, [UserRole::Admin, UserRole::Roastery], true)) {
            if ($this->record->cafe_id) {
                abort_unless($role === UserRole::Admin, 403);
                $data['cafe_id'] = AuthenticatedUser::require()->cafes()->whereKey($this->record->cafe_id)->value('id');
                abort_unless($data['cafe_id'], 403);
                $data['roastery_id'] = null;
            } else {
                $data['roastery_id'] = AuthenticatedUser::require()->roasteries()->whereKey($this->record->roastery_id)->value('id');
                abort_unless($data['roastery_id'], 403);
                $data['cafe_id'] = null;
            }
        }
        abort_if(! empty($data['cafe_id']) === ! empty($data['roastery_id']), 422, 'Pilih tepat satu kedai untuk produk ini.');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $lockedRecord = MenuItem::query()->lockForUpdate()->findOrFail($record->getKey());

            if ((int) ($data['stock'] ?? 0) < (int) $lockedRecord->reserved_stock) {
                throw ValidationException::withMessages([
                    'data.stock' => "Stok fisik tidak boleh kurang dari {$lockedRecord->reserved_stock} unit yang sedang dipesan.",
                ]);
            }

            unset($data['reserved_stock']);
            $lockedRecord->update($data);

            return $lockedRecord;
        }, 3);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->disabled(fn () => (int) $this->record->reserved_stock > 0)];
    }
}
