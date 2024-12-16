<?php

namespace App\Filament\Clusters\AdmCompras\Resources\ComprasResource\Pages;

use App\Filament\Clusters\AdmCompras\Resources\ComprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompras extends EditRecord
{
    protected static string $resource = ComprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
