<?php

namespace App\Filament\Clusters\tiadmin\Resources\NotasdeCreditoResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\NotasdeCreditoResource;
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
