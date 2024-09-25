<?php

namespace App\Filament\Resources\CatCuentasResource\Pages;

use App\Filament\Resources\CatCuentasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatCuentas extends EditRecord
{
    protected static string $resource = CatCuentasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
