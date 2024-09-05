<?php

namespace App\Filament\Resources\XmlfilesResource\Pages;

use App\Filament\Resources\XmlfilesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditXmlfiles extends EditRecord
{
    protected static string $resource = XmlfilesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
