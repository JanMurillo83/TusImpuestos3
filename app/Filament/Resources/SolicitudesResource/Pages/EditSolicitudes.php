<?php

namespace App\Filament\Resources\SolicitudesResource\Pages;

use App\Filament\Resources\SolicitudesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitudes extends EditRecord
{
    protected static string $resource = SolicitudesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
