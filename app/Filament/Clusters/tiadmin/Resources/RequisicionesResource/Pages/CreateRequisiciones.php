<?php

namespace App\Filament\Clusters\tiadmin\Resources\RequisicionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\RequisicionesResource;
use App\Filament\Support\HasDownloadRedirect;
use App\Models\RequisicionesPartidas;
use App\Models\SeriesFacturas;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class CreateRequisiciones extends CreateRecord
{
    use HasDownloadRedirect;

    protected static string $resource = RequisicionesResource::class;
    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $serieId = intval($data['sel_serie'] ?? 0);
        if (! $serieId) {
            throw new \Exception('Debe seleccionar una serie para la requisicion.');
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

        $partidas = RequisicionesPartidas::where('requisiciones_id', $record->id)->get();
        foreach ($partidas as $partida) {
            $partida->pendientes = $partida->cant;
            $partida->save();
        }

        $archivo_pdf = 'REQUISICION' . $record->id . '.pdf';
        $ruta = public_path() . '/TMPCFDI/' . $archivo_pdf;
        if (File::exists($ruta)) {
            File::delete($ruta);
        }
        $data = ['idrequisicion' => $record->id, 'team_id' => Filament::getTenant()->id, 'prov_id' => $record->prov];
        $html = View::make('NFTO_Requisicion', $data)->render();
        Browsershot::html($html)->format('Letter')
            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
            ->noSandbox()
            ->scale(0.8)->savePdf($ruta);

        $this->setDownloadFilename($archivo_pdf);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getDownloadRedirectUrl() ?? static::getResource()::getUrl('index');
    }
}
