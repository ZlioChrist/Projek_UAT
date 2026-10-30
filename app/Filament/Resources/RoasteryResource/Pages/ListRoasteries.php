<?php

namespace App\Filament\Resources\RoasteryResource\Pages;

use App\Filament\Resources\RoasteryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoasteries extends ListRecords
{
    protected static string $resource = RoasteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
