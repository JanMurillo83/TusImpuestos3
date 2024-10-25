<?php

namespace App\Filament\Resources\BancoCuentasResource\Pages;

use App\Filament\Resources\BancoCuentasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBancoCuentas extends ListRecords
{
    protected static string $resource = BancoCuentasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
