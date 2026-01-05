<?php

namespace App\Filament\Clusters\tiadmin\Resources\InventarioResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\InventarioResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
