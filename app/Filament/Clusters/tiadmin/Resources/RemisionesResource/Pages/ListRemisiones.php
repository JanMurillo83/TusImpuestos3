<?php

namespace App\Filament\Clusters\tiadmin\Resources\RemisionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\RemisionesResource;
use Filament\Resources\Pages\ListRecords;

class ListRemisiones extends ListRecords
{
    protected static string $resource = RemisionesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function callImprimir($record)
    {
        $this->dispatch('open-modal', id: 'Imprimir_Doc', record: $record->id);
    }
}
