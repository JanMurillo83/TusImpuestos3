<?php

namespace App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeriesFacturas extends EditRecord
{
    protected static string $resource = SeriesFacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
