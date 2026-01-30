<?php

namespace App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCotizaciones extends EditRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->record->recalculateTotalsFromPartidas();
    }
}
