<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovbancos extends EditRecord
{
    protected static string $resource = MovbancosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
