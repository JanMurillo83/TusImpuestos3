<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEsquemasimps extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = EsquemasimpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
