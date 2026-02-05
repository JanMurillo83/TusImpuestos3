<?php

namespace App\Filament\Clusters\tiadmin\Resources\InsumosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\InsumosResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInsumos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = InsumosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
