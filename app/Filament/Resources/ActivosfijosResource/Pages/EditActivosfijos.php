<?php

namespace App\Filament\Resources\ActivosfijosResource\Pages;

use App\Filament\Resources\ActivosfijosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivosfijos extends EditRecord
{
    protected static string $resource = ActivosfijosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
