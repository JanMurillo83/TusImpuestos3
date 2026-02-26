<?php

namespace App\Filament\Clusters\tiadmin\Resources\PedidosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\PedidosResource;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\FacturasPartidas;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\SeriesFacturas;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidosResource::class;
    public $requ;
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Facturar Cotización')
                ->icon('fas-file-invoice')
                ->color(Color::Green)
                ->visible(false)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->mountUsing(function (Form $form, $livewire, Actions\Action $action) {
                    $action->close();
                    $cotId = $livewire->requ;
                    $record = Cotizaciones::find($cotId);
                    if (!$record) return;

                    $partidas = CotizacionesPartidas::where('cotizaciones_id', $record->id)
                        ->where(function ($q) {
                            $q->whereNull('pendientes')->orWhere('pendientes', '>', 0);
                        })
                        ->get()
                        ->map(function ($partida) {
                            return [
                                'partida_id' => $partida->id,
                                'item' => $partida->item,
                                'descripcion' => $partida->descripcion,
                                'cantidad_original' => $partida->cant,
                                'cantidad_pendiente' => $partida->pendientes ?? $partida->cant,
                                'cantidad_a_facturar' => $partida->pendientes ?? $partida->cant,
                                'precio' => $partida->precio,
                            ];
                        })->toArray();

                    $form->fill([
                        'partidas' => $partidas,
                    ]);
                })
                ->form([
                    Section::make('Información de la Cotización')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Placeholder::make('origen_folio')
                                        ->label('Folio Cotización')
                                        ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->folio),
                                    Placeholder::make('origen_fecha')
                                        ->label('Fecha')
                                        ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->fecha),
                                    Placeholder::make('origen_cliente')
                                        ->label('Cliente')
                                        ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->nombre),
                                ]),
                        ]),
                    Repeater::make('partidas')
                        ->label('Partidas Pendientes')
                        ->schema([
                            Hidden::make('partida_id'),
                            Grid::make(4)
                                ->schema([
                                    Placeholder::make('item_desc')
                                        ->label('Producto / Descripción')
                                        ->content(fn ($get) => ($get('item') ? '[' . \App\Models\Inventario::find($get('item'))?->clave . '] ' : '') . $get('descripcion'))
                                        ->columnSpan(2),
                                    Placeholder::make('pendiente')
                                        ->label('Pendiente')
                                        ->content(fn ($get) => $get('cantidad_pendiente')),
                                    TextInput::make('cantidad_a_facturar')
                                        ->label('A Facturar')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.01)
                                        ->maxValue(fn ($get) => $get('cantidad_pendiente'))
                                        ->reactive(),
                                ]),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                    \Filament\Forms\Components\Actions::make([
                        FormAction::make('Generar Pedido')
                            ->color(Color::Green)
                            ->action(function (array $data, Get $get, $livewire, FormAction $action) {
                                $cot = Cotizaciones::find($livewire->requ);
                                if (!$cot) return;

                                $partidasSeleccionadas = collect($get('partidas'))->filter(fn($p) => $p['cantidad_a_facturar'] > 0);

                                if ($partidasSeleccionadas->isEmpty()) {
                                    Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                                    return;
                                }

                                DB::beginTransaction();
                                try {
                                    $ser = 'P';
                                    $fol = Pedidos::where('team_id',Filament::getTenant()->id)->max('folio') + 1;
                                    $doc =$ser.$fol;
                                    $factura = \App\Models\Pedidos::create([
                                        'serie' => $ser,
                                        'folio' => $fol,
                                        'docto' => $doc,
                                        'fecha' => now()->format('Y-m-d'),
                                        'clie' => $cot->clie,
                                        'nombre' => $cot->nombre,
                                        'esquema' => $cot->esquema,
                                        'subtotal' => 0,
                                        'iva' => 0,
                                        'retiva' => 0,
                                        'retisr' => 0,
                                        'ieps' => 0,
                                        'total' => 0,
                                        'moneda' => $cot->moneda ?? 'MXN',
                                        'tcambio' => $cot->tcambio ?? 1,
                                        'observa' => 'Generada desde Cotización #' . $cot->folio,
                                        'estado' => 'Activa',
                                        'cotizacion_id' => $cot->id,
                                        'team_id' => Filament::getTenant()->id,
                                        'metodo' => $cot->metodo ?? 'PUE',
                                        'forma' => $cot->forma ?? '01',
                                        'uso' => $cot->uso ?? 'G03',
                                        'condiciones' => $cot->condiciones ?? 'CONTADO',
                                    ]);

                                    $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                                    foreach ($partidasSeleccionadas as $pData) {
                                        $parOriginal = CotizacionesPartidas::find($pData['partida_id']);
                                        if (!$parOriginal) continue;

                                        $cantFacturar = $pData['cantidad_a_facturar'];
                                        $factor = $parOriginal->cant > 0 ? ($cantFacturar / $parOriginal->cant) : 0;

                                        $lineSubtotal = $parOriginal->precio * $cantFacturar;
                                        $lineIva = $parOriginal->iva * $factor;
                                        $lineRetIva = $parOriginal->retiva * $factor;
                                        $lineRetIsr = $parOriginal->retisr * $factor;
                                        $lineIeps = $parOriginal->ieps * $factor;
                                        $lineTotal = $lineSubtotal + $lineIva - $lineRetIva - $lineRetIsr + $lineIeps;

                                        PedidosPartidas::create([
                                            'pedidos_id' => $factura->id,
                                            'item' => $parOriginal->item,
                                            'descripcion' => $parOriginal->descripcion,
                                            'cant' => $cantFacturar,
                                            'precio' => $parOriginal->precio,
                                            'subtotal' => $lineSubtotal,
                                            'iva' => $lineIva,
                                            'retiva' => $lineRetIva,
                                            'retisr' => $lineRetIsr,
                                            'ieps' => $lineIeps,
                                            'total' => $lineTotal,
                                            'unidad' => $parOriginal->unidad,
                                            'cvesat' => $parOriginal->cvesat,
                                            'costo' => $parOriginal->costo,
                                            'clie' => $cot->clie,
                                            'team_id' => Filament::getTenant()->id,
                                            'cotizacion_partida_id' => $parOriginal->id,
                                        ]);

                                        $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva;
                                        $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;

                                        $parOriginal->update(['pendientes' => max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantFacturar)]);
                                    }

                                    $factura->update([
                                        'subtotal' => $subtotal, 'iva' => $iva, 'retiva' => $retiva,
                                        'retisr' => $retisr, 'ieps' => $ieps, 'total' => $total,
                                    ]);

                                    $pendientesTotales = CotizacionesPartidas::where('cotizaciones_id', $cot->id)->sum('pendientes');
                                    $cot->update(['estado' => $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial']);

                                    DB::commit();
                                    // Nota: No se incrementa folio de facturas porque esto es un PEDIDO, no una factura
                                    Notification::make()->title('Pedido generado exitosamente')->success()->send();
                                    $action->close();
                                    $livewire->dispatch('close-modal', ['id' => $action->getName()]);
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    Notification::make()->title('Error al generar factura: ' . $e->getMessage())->danger()->send();
                                }
                            }),
                        FormAction::make('Cancelar')
                            ->color(Color::Red)
                            ->action(fn(FormAction $action) => $action->close()),
                    ])
                ])
        ];
    }

    public function callImprimir($record)
    {

        $tabla = $this->getTable();
        $tabla->getAction('Imprimir_Doc')->visible(true);
        $this->replaceMountedTableAction('Imprimir_Doc');
        $tabla->getAction('Imprimir_Doc')->visible(false);
    }
}
