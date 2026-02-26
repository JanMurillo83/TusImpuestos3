<?php

namespace App\Filament\Clusters\tiadmin\Resources\ClientesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ClientesResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
