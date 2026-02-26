<?php

namespace App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource\Pages;

use App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIngresosEgresos extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = IngresosEgresosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
