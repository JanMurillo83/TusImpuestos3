<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProyectos extends EditRecord
{
    protected static string $resource = ProyectosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
