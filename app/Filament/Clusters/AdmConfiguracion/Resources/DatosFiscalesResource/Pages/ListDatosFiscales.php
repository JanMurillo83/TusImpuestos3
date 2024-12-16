<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource\Pages;

use App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDatosFiscales extends ListRecords
{
    protected static string $resource = DatosFiscalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
