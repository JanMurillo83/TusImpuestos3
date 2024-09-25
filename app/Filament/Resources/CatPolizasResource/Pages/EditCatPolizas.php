<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatPolizas extends EditRecord
{
    protected static string $resource = CatPolizasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
