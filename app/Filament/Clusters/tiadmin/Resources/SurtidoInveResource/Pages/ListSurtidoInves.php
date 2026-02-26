<?php

namespace App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListSurtidoInves extends ListRecords
{
    protected static string $resource = SurtidoInveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
