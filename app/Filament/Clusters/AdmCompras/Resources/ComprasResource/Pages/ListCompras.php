<?php

namespace App\Filament\Clusters\AdmCompras\Resources\ComprasResource\Pages;

use App\Filament\Clusters\AdmCompras\Resources\ComprasResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompras extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ComprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
