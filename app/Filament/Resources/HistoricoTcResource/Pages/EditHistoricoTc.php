<?php

namespace App\Filament\Resources\HistoricoTcResource\Pages;

use App\Filament\Resources\HistoricoTcResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHistoricoTc extends EditRecord
{
    protected static string $resource = HistoricoTcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
