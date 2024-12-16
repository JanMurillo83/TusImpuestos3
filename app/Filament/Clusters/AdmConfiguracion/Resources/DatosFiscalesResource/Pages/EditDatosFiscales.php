<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource\Pages;

use App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDatosFiscales extends EditRecord
{
    protected static string $resource = DatosFiscalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
