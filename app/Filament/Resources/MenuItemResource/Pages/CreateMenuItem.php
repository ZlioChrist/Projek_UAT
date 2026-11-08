<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = AuthenticatedUser::require();
        $role = UserRole::fromUserValue($user?->role);

        if ($role === UserRole::Admin) {
            $data['cafe_id'] = $user->cafes()->whereKey($data['cafe_id'] ?? 0)->value('id');
            abort_unless($data['cafe_id'], 403);
            $data['roastery_id'] = null;
        } elseif ($role === UserRole::Roastery) {
            $data['roastery_id'] = $user->roasteries()->whereKey($data['roastery_id'] ?? 0)->value('id');
            abort_unless($data['roastery_id'], 403);
            $data['cafe_id'] = null;
        }

        abort_if(! empty($data['cafe_id']) === ! empty($data['roastery_id']), 422, 'Pilih tepat satu kedai untuk produk ini.');

        return $data;
    }
}
