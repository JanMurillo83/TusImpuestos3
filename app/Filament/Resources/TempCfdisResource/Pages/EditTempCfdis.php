<?php

namespace App\Filament\Resources\TempCfdisResource\Pages;

use App\Filament\Resources\TempCfdisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTempCfdis extends EditRecord
{
    protected static string $resource = TempCfdisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
