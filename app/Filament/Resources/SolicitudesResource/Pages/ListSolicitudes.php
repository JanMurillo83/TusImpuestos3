<?php

namespace App\Filament\Resources\SolicitudesResource\Pages;

use App\Filament\Resources\SolicitudesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudes extends ListRecords
{
    protected static string $resource = SolicitudesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
