<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProveedores extends EditRecord
{
    protected static string $resource = ProveedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
