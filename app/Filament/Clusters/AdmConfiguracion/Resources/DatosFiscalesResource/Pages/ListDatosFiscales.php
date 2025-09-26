<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource\Pages;

use App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDatosFiscales extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = DatosFiscalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
