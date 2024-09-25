<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatPolizas extends ListRecords
{
    protected static string $resource = CatPolizasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Poliza')
                ->icon('fas-square-plus'),
        ];
    }
}
