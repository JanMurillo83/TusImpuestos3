<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEsquemasimps extends ListRecords
{
    protected static string $resource = EsquemasimpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
