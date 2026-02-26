<?php

namespace App\Filament\Clusters\tiadmin\Resources\RequisicionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\RequisicionesResource;
use App\Filament\Resources\Pages\ListRecords;

class ListRequisiciones extends ListRecords
{
    protected static string $resource = RequisicionesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
