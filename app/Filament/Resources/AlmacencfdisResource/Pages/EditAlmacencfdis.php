<?php

namespace App\Filament\Resources\AlmacencfdisResource\Pages;

use App\Filament\Resources\AlmacencfdisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlmacencfdis extends EditRecord
{
    protected static string $resource = AlmacencfdisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
