<?php

namespace App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource;
use App\Filament\Support\HasDownloadRedirect;
use App\Models\CotizacionesPartidas;
use App\Models\SeriesFacturas;
use App\Support\DocumentFilename;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class CreateCotizaciones extends CreateRecord
{
    use HasDownloadRedirect;

    protected static string $resource = CotizacionesResource::class;
    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $serieId = intval($data['sel_serie'] ?? 0);
        if (! $serieId) {
            throw new \Exception('Debe seleccionar una serie para la cotizacion.');
        }

        $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

        $data['serie'] = $folioData['serie'];
        $data['folio'] = $folioData['folio'];
        $data['docto'] = $folioData['docto'];
        $data['created_by_user_id'] = Filament::auth()->id();
        if (empty($data['nombre_elaboro'])) {
            $data['nombre_elaboro'] = Filament::auth()->user()->name;
        }
        $data['estado_comercial'] = $data['estado_comercial'] ?? 'OPEN';
        $data['probabilidad'] = $data['probabilidad'] ?? 0.20;
        $data['descuento_pct'] = $data['descuento_pct'] ?? 0;

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
        $record->syncClienteNombre();
        $record->fixPartidasSubtotalFromCantidadPrecio();
        $record->recalculateTotalsFromPartidas();

        $partidas = CotizacionesPartidas::where('cotizaciones_id', $record->id)->get();
        foreach ($partidas as $partida) {
            $partida->update(['pendientes' => $partida->cant]);
        }

        $idorden = $record->id;
        $id_empresa = Filament::getTenant()->id;
        $archivo_pdf = DocumentFilename::build('COTIZACION', $record->docto ?? ($record->serie . $record->folio), $record->nombre, $record->fecha);
        $ruta = public_path() . '/TMPCFDI/' . $archivo_pdf;
        if (File::exists($ruta)) {
            File::delete($ruta);
        }
        $data = ['idcotiza' => $idorden, 'team_id' => $id_empresa, 'clie_id' => $record->clie];
        $html = View::make('NFTO_Cotizacion', $data)->render();
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
