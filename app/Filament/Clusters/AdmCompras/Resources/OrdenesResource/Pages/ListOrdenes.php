<?php

namespace App\Filament\Clusters\AdmCompras\Resources\OrdenesResource\Pages;

use App\Filament\Clusters\AdmCompras\Resources\OrdenesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenes extends ListRecords
{
    protected static string $resource = OrdenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
