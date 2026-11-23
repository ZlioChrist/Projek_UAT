<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof User) {
            return $data;
        }

        $record->loadMissing(['cafes', 'roasteries']);

        foreach ($record->cafes as $cafe) {
            $data['business_name_cafe_'.$cafe->getKey()] = $cafe->name;
            $data['business_status_cafe_'.$cafe->getKey()] = $cafe->status;
        }

        foreach ($record->roasteries as $roastery) {
            $data['business_name_roastery_'.$roastery->getKey()] = $roastery->name;
            $data['business_status_roastery_'.$roastery->getKey()] = $roastery->status;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $businessUpdates = [];
        foreach ($data as $key => $value) {
            if (preg_match('/^business_(name|status)_(cafe|roastery)_(\d+)$/', (string) $key, $matches) !== 1) {
                continue;
            }

            if ($matches[1] === 'status' && ! filled($value)) {
                unset($data[$key]);

                continue;
            }

            $updateKey = $matches[2].':'.$matches[3];
            $businessUpdates[$updateKey] ??= [
                'type' => $matches[2],
                'id' => $matches[3],
                'fields' => [],
            ];
            $businessUpdates[$updateKey]['fields'][$matches[1]] = $value;
            unset($data[$key]);
        }

        return DB::transaction(function () use ($record, $data, $businessUpdates): Model {
            $record = parent::handleRecordUpdate($record, $data);

            if ($businessUpdates === []) {
                return $record;
            }

            if (! $record instanceof User) {
                throw new \LogicException('The edited record must be a user.');
            }

            foreach ($businessUpdates as $update) {
                if (isset($update['fields']['status']) && ! in_array($update['fields']['status'], ['published', 'review', 'draft'], true)) {
                    throw ValidationException::withMessages([
                        'data.business_status_'.$update['type'].'_'.$update['id'] => 'Pilih status usaha yang tersedia.',
                    ]);
                }

                $business = match ($update['type']) {
                    'cafe' => $record->cafes()->whereKey($update['id'])->firstOrFail(),
                    'roastery' => $record->roasteries()->whereKey($update['id'])->firstOrFail(),
                };

                if (array_key_exists('name', $update['fields'])) {
                    $business->name = $update['fields']['name'];
                }

                if (array_key_exists('status', $update['fields'])) {
                    $business->status = $update['fields']['status'];
                }

                $business->save();
            }

            return $record;
        });
    }
}
