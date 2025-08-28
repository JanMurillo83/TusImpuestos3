<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEsquemasimp extends EditRecord
{
    protected static string $resource = EsquemasimpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
