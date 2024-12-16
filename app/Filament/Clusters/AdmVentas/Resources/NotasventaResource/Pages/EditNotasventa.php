<?php

namespace App\Filament\Clusters\AdmVentas\Resources\NotasventaResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\NotasventaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotasventa extends EditRecord
{
    protected static string $resource = NotasventaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
