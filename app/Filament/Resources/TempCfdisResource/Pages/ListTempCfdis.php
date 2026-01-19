<?php

namespace App\Filament\Resources\TempCfdisResource\Pages;

use App\Filament\Resources\TempCfdisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTempCfdis extends ListRecords
{
    protected static string $resource = TempCfdisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
