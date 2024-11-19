<?php

namespace App\Filament\Resources\MovinventariosResource\Pages;

use App\Filament\Resources\MovinventariosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovinventarios extends EditRecord
{
    protected static string $resource = MovinventariosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
