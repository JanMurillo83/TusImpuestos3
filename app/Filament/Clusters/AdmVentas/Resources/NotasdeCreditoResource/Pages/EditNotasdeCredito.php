<?php

namespace App\Filament\Clusters\AdmVentas\Resources\NotasdeCreditoResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\NotasdeCreditoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotasdeCredito extends EditRecord
{
    protected static string $resource = NotasdeCreditoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
