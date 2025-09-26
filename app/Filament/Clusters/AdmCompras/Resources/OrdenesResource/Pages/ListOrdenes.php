<?php

namespace App\Filament\Clusters\AdmCompras\Resources\OrdenesResource\Pages;

use App\Filament\Clusters\AdmCompras\Resources\OrdenesResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenes extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = OrdenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
