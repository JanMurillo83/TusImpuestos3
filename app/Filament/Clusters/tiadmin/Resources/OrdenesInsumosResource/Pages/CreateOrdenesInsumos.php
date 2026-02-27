<?php

namespace App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\OrdenesInsumosResource;
use App\Filament\Support\HasDownloadRedirect;
use App\Models\OrdenesInsumosPartidas;
use App\Models\SeriesFacturas;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;

class CreateOrdenesInsumos extends CreateRecord
{
    use HasDownloadRedirect;

    protected static string $resource = OrdenesInsumosResource::class;
    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $serieId = intval($data['sel_serie'] ?? 0);
        if (! $serieId) {
            throw new \Exception('Debe seleccionar una serie para la orden de insumos.');
        }

        $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);
        $data['serie'] = $folioData['serie'];
        $data['folio'] = $folioData['folio'];
        $data['docto'] = $folioData['docto'];

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Grabar')
            ->color(Color::Green)
            ->icon('fas-save');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->visible(false);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cerrar')
            ->color(Color::Red)
            ->icon('fas-ban');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $record->refresh();
        $record->recalculatePartidasFromItemSchema();
        $record->recalculateTotalsFromPartidas();

        $partidas = OrdenesInsumosPartidas::where('ordenes_insumos_id', $record->id)->get();
        foreach ($partidas as $partida) {
            $partida->pendientes = $partida->cant;
            $partida->save();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getDownloadRedirectUrl() ?? static::getResource()::getUrl('index');
    }
}
