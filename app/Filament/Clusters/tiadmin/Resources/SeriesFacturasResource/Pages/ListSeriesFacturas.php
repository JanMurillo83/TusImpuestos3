<?php

namespace App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeriesFacturas extends ListRecords
{
    protected static string $resource = SeriesFacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
