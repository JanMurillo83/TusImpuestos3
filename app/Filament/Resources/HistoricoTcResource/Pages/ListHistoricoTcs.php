<?php

namespace App\Filament\Resources\HistoricoTcResource\Pages;

use App\Filament\Resources\HistoricoTcResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistoricoTcs extends ListRecords
{
    protected static string $resource = HistoricoTcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->createAnother(false)
            ->modalWidth('sm'),
        ];
    }
}
