<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\LineasprodResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\LineasprodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLineasprods extends ListRecords
{
    protected static string $resource = LineasprodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
