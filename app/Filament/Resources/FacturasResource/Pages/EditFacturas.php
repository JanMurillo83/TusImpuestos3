<?php

namespace App\Filament\Resources\FacturasResource\Pages;

use App\Filament\Resources\FacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacturas extends EditRecord
{
    protected static string $resource = FacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
