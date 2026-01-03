<?php

namespace App\Filament\Clusters\tiadmin\Resources\ProveedoresResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ProveedoresResource;
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
