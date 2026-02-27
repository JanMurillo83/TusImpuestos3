<?php

namespace App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource;
use App\Filament\Support\HasDownloadRedirect;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditOrdenesInsumos extends EditRecord
{
    use HasDownloadRedirect;

    protected static string $resource = OrdenesInsumosResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Grabar')
            ->color(Color::Green)
            ->icon('fas-save');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cerrar')
            ->color(Color::Red)
            ->icon('fas-ban');
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $record->refresh();
        $record->recalculatePartidasFromItemSchema();
        $record->recalculateTotalsFromPartidas();
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getDownloadRedirectUrl() ?? static::getResource()::getUrl('index');
    }
}
