<?php

namespace App\Filament\Clusters\AdmMovimientos\Resources\MovinventarioResource\Pages;

use App\Filament\Clusters\AdmMovimientos\Resources\MovinventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovinventario extends EditRecord
{
    protected static string $resource = MovinventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
