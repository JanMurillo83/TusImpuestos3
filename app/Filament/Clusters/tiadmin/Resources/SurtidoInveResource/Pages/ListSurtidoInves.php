<?php

namespace App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Inventario;
use App\Models\SurtidoInve;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSurtidoInves extends ListRecords
{
    protected static string $resource = SurtidoInveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('imprimirDocumento')
                ->label('Imprimir por Documento')
                ->icon('fas-print')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('factura_id')
                        ->label('Documento')
                        ->options(function () {
                            $teamId = Filament::getTenant()->id;
                            $facturaIds = SurtidoInve::where('team_id', $teamId)
                                ->select('factura_id')
                                ->distinct()
                                ->pluck('factura_id');

                            if ($facturaIds->isEmpty()) {
                                return [];
                            }

                            return Facturas::where('team_id', $teamId)
                                ->whereIn('id', $facturaIds)
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(function (Facturas $factura) {
                                    $folio = $factura->docto ?: trim(($factura->serie ?? '') . ($factura->folio ?? ''));
                                    $label = $folio !== '' ? $folio : 'Factura ' . $factura->id;
                                    if (!empty($factura->nombre)) {
                                        $label .= ' - ' . $factura->nombre;
                                    }
                                    return [$factura->id => $label];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $teamId = Filament::getTenant()->id;
                    $facturaId = (int) $data['factura_id'];

                    $factura = Facturas::where('team_id', $teamId)->find($facturaId);
                    if (!$factura) {
                        Notification::make()->title('Documento no encontrado')->danger()->send();
                        return;
                    }

                    $surtidos = SurtidoInve::where('team_id', $teamId)
                        ->where('factura_id', $facturaId)
                        ->orderBy('id')
                        ->get();

                    if ($surtidos->isEmpty()) {
                        Notification::make()->title('Sin partidas de surtido')->warning()->send();
                        return;
                    }

                    $items = Inventario::whereIn('id', $surtidos->pluck('item_id')->unique())
                        ->get()
                        ->keyBy('id');

                    $dafis = DatosFiscales::where('team_id', $teamId)->first();

                    $pdf = Pdf::loadView('RepSurtidoDocumento', [
                        'dafis' => $dafis,
                        'factura' => $factura,
                        'surtidos' => $surtidos,
                        'items' => $items,
                    ]);

                    return response()->streamDownload(fn () => print($pdf->output()), "Surtido_{$facturaId}.pdf");
                }),
        ];
    }
}
