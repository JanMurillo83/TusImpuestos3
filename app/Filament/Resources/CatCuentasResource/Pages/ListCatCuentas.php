<?php

namespace App\Filament\Resources\CatCuentasResource\Pages;

use App\Filament\Resources\CatCuentasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatCuentas extends ListRecords
{
    protected static string $resource = CatCuentasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Alta de Cuenta')
                ->icon('fas-square-plus'),
        ];
    }
}
