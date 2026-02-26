<?php

namespace App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListOrdenesInsumos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = OrdenesInsumosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
