<?php

namespace App\Filament\Resources\MovinventariosResource\Pages;

use App\Filament\Resources\MovinventariosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovinventarios extends ListRecords
{
    protected static string $resource = MovinventariosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
