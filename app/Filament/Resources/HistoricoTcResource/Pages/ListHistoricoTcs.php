<?php

namespace App\Filament\Resources\HistoricoTcResource\Pages;

use App\Filament\Resources\HistoricoTcResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListHistoricoTcs extends ListRecords
{
    use HasResizableColumn;
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
