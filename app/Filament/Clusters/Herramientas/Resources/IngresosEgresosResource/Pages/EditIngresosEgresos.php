<?php

namespace App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource\Pages;

use App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIngresosEgresos extends EditRecord
{
    protected static string $resource = IngresosEgresosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
