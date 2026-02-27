<?php

namespace App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource;
use App\Filament\Support\HasDownloadRedirect;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Support\DocumentFilename;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class EditCotizaciones extends EditRecord
{
    use HasDownloadRedirect;

    protected static string $resource = CotizacionesResource::class;

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
        $record->syncClienteNombre();
        $record->fixPartidasSubtotalFromCantidadPrecio();
        $record->recalculateTotalsFromPartidas();

        $partidas = CotizacionesPartidas::where('cotizaciones_id', $record->id)->get();
        foreach ($partidas as $partida) {
            $partida->update(['pendientes' => $partida->cant]);
        }

        if (! $record->nombre_elaboro) {
            Cotizaciones::where('id', $record->id)->update([
                'nombre_elaboro' => Filament::auth()->user()->name,
            ]);
        }

        $clien = Clientes::where('id', $record->clie)->first();
        if ($clien) {
            Cotizaciones::where('id', $record->id)->update([
                'nombre' => $clien->nombre,
            ]);
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

    protected function getRedirectUrl(): ?string
    {
        return $this->getDownloadRedirectUrl() ?? static::getResource()::getUrl('index');
    }
}
