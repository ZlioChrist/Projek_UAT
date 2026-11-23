<?php

namespace App\Filament\Resources\OrderReportResource\Pages;

use App\Filament\Resources\OrderReportResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderReports extends ListRecords
{
    protected static string $resource = OrderReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
