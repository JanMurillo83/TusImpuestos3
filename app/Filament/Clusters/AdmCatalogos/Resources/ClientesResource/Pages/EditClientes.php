<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientes extends EditRecord
{
    protected static string $resource = ClientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
