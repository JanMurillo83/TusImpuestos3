<?php

namespace App\Filament\Clusters\tiadmin\Resources\FacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\FacturasResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Facturas;
use App\Models\FacturasPartidas;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\SeriesFacturas;
use App\Models\SurtidoInve;
use App\Models\TableSettings;
use App\Models\Team;
use Asmit\ResizedColumn\HasResizableColumn;
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
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListFacturas extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = FacturasResource::class;

    public ?int $idorden;
    public ?int $id_empresa;
    public $requ;
    protected function getHeaderActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_P')
            ->visible(false)
            ->print(false)
            ->savePdf()
            ->preview()
            ->format('letter', 'mm')
            ->content(fn() => view('RepFactura',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
            ->modalWidth('7xl')
            ->filename(function () {
                $record = Facturas::where('id',$this->idorden)->first();
                $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                $cli = Clientes::where('id',$record->clie)->first();
                return $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
            }),
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(false)
                ->margin([0,0,0,2])
                ->content(fn() => view('RepFactura',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->format('letter', 'in')
                ->scale(0.80)
                ->filename(function () {
                    $record = Facturas::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    return $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
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
                            Fieldset::make('Serie Factura')
                                ->schema([
                                    Select::make('sel_serie')
                                        ->label('Serie')
                                        ->live(onBlur: true)
                                        ->options(SeriesFacturas::where('team_id',Filament::getTenant()->id)
                                            ->where('tipo','F')
                                            ->select(DB::raw("id,CONCAT(serie,'-',COALESCE(descripcion,'Default')) as descripcion"))
                                            ->pluck('descripcion','id'))
                                        ->default(function (){
                                            return SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->serie ?? 'A';
                                        })->afterStateUpdated(function(Get $get,Set $set){
                                            $ser = $get('sel_serie');
                                            $fol = SeriesFacturas::where('id',$ser)->first();
                                            $set('serie',$fol->serie);
                                            $set('folio',$fol->folio + 1);
                                            $set('docto',$fol->serie.$fol->folio + 1);
                                        })->columnSpan(2),
                                        TextInput::make('serie')->readOnly(),
                                        TextInput::make('folio')->readOnly(),
                                        TextInput::make('docto')
                                            ->label('Documento')
                                            ->required()
                                            ->readOnly(),
                                ])->columns(5),

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
                        FormAction::make('Generar Factura')
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
                                    $ser = $get('serie');
                                    $fol = $get('folio');
                                    $doc = $get('docto');
                                    $factura = \App\Models\Facturas::create([
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

                                        $part_n = FacturasPartidas::create([
                                            'facturas_id' => $factura->id,
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
                                        SurtidoInve::create([
                                            'factura_id'=>$factura->id,
                                            'factura_partida_id'=>$part_n->id,
                                            'item_id'=>$parOriginal->item,
                                            'descr'=>$parOriginal->descripcion,
                                            'cant'=>$cantFacturar,
                                            'precio_u'=>$parOriginal->precio,
                                            'costo_u'=>$parOriginal->costo,
                                            'precio_total'=>$parOriginal->precio*$parOriginal->cant,
                                            'costo_total'=>$parOriginal->costo*$parOriginal->cant,
                                            'team_id'=>Filament::getTenant()->id
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
                                    $ser = intval($get('sel_serie'));
                                    SeriesFacturas::where('id',$ser)->increment('folio',1);
                                    Notification::make()->title('Factura generada exitosamente')->success()->send();
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
                ]),
            Actions\Action::make('Facturar Pedido')
                ->icon('fas-file-invoice')
                ->color(Color::Green)
                ->visible(false)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->mountUsing(function (Form $form, $livewire, Actions\Action $action) {
                    $action->close();
                    $cotId = $livewire->requ;
                    $record = Pedidos::find($cotId);
                    if (!$record) return;

                    $partidas = PedidosPartidas::where('pedidos_id', $record->id)
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
                    Section::make('Información del Pedido')
                        ->schema([
                            Fieldset::make('Serie Factura')
                                ->schema([
                                    Select::make('sel_serie')
                                        ->label('Serie')
                                        ->live(onBlur: true)
                                        ->options(SeriesFacturas::where('team_id',Filament::getTenant()->id)
                                            ->where('tipo','F')
                                            ->select(DB::raw("id,CONCAT(serie,'-',COALESCE(descripcion,'Default')) as descripcion"))
                                            ->pluck('descripcion','id'))
                                        ->default(function (){
                                            return SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->serie ?? 'A';
                                        })->afterStateUpdated(function(Get $get,Set $set){
                                            $ser = $get('sel_serie');
                                            $fol = SeriesFacturas::where('id',$ser)->first();
                                            $set('serie',$fol->serie);
                                            $set('folio',$fol->folio + 1);
                                            $set('docto',$fol->serie.$fol->folio + 1);
                                        })->columnSpan(2),
                                    TextInput::make('serie')->readOnly(),
                                    TextInput::make('folio')->readOnly(),
                                    TextInput::make('docto')
                                        ->label('Documento')
                                        ->required()
                                        ->readOnly(),
                                ])->columns(5),

                            Grid::make(3)
                                ->schema([
                                    Placeholder::make('origen_folio')
                                        ->label('Folio Pedido')
                                        ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->folio),
                                    Placeholder::make('origen_fecha')
                                        ->label('Fecha')
                                        ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->fecha),
                                    Placeholder::make('origen_cliente')
                                        ->label('Cliente')
                                        ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->nombre),
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
                        FormAction::make('Generar Factura')
                            ->color(Color::Green)
                            ->action(function (array $data, Get $get, $livewire, FormAction $action) {
                                $cot = Pedidos::find($livewire->requ);
                                if (!$cot) return;

                                $partidasSeleccionadas = collect($get('partidas'))->filter(fn($p) => $p['cantidad_a_facturar'] > 0);

                                if ($partidasSeleccionadas->isEmpty()) {
                                    Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                                    return;
                                }

                                DB::beginTransaction();
                                try {
                                    $ser = $get('serie');
                                    $fol = $get('folio');
                                    $doc = $get('docto');
                                    $factura = \App\Models\Facturas::create([
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
                                        'observa' => 'Generada desde Pedido #' . $cot->folio,
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
                                        $parOriginal = PedidosPartidas::find($pData['partida_id']);
                                        if (!$parOriginal) continue;

                                        $cantFacturar = $pData['cantidad_a_facturar'];
                                        $factor = $parOriginal->cant > 0 ? ($cantFacturar / $parOriginal->cant) : 0;

                                        $lineSubtotal = $parOriginal->precio * $cantFacturar;
                                        $lineIva = $parOriginal->iva * $factor;
                                        $lineRetIva = $parOriginal->retiva * $factor;
                                        $lineRetIsr = $parOriginal->retisr * $factor;
                                        $lineIeps = $parOriginal->ieps * $factor;
                                        $lineTotal = $lineSubtotal + $lineIva - $lineRetIva - $lineRetIsr + $lineIeps;

                                        $part_n = FacturasPartidas::create([
                                            'facturas_id' => $factura->id,
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
                                        SurtidoInve::create([
                                            'factura_id'=>$factura->id,
                                            'factura_partida_id'=>$part_n->id,
                                            'item_id'=>$parOriginal->item,
                                            'descr'=>$parOriginal->descripcion,
                                            'cant'=>$cantFacturar,
                                            'precio_u'=>$parOriginal->precio,
                                            'costo_u'=>$parOriginal->costo,
                                            'precio_total'=>$parOriginal->precio*$parOriginal->cant,
                                            'costo_total'=>$parOriginal->costo*$parOriginal->cant,
                                            'team_id'=>Filament::getTenant()->id
                                        ]);
                                        $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva;
                                        $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;

                                        $parOriginal->update(['pendientes' => max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantFacturar)]);
                                    }

                                    $factura->update([
                                        'subtotal' => $subtotal, 'iva' => $iva, 'retiva' => $retiva,
                                        'retisr' => $retisr, 'ieps' => $ieps, 'total' => $total,
                                    ]);

                                    $pendientesTotales = PedidosPartidas::where('pedidos_id', $cot->id)->sum('pendientes');
                                    $cot->update(['estado' => $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial']);

                                    DB::commit();
                                    $ser = intval($get('sel_serie'));
                                    SeriesFacturas::where('id',$ser)->increment('folio',1);
                                    Notification::make()->title('Factura generada exitosamente')->success()->send();
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
                ]),
        ];
    }
    protected function persistColumnWidthsToDatabase(): void
    {
        // Your custom database save logic here
        TableSettings::updateOrCreate(
            [
                'user_id' => $this->getUserId(),
                'resource' => $this->getResourceModelFullPath(), // e.g., 'App\Models\User'
                'team_id' => Filament::getTenant()->id,
            ],
            ['settings' => $this->columnWidths]
        );
    }
    public function callImprimir($record)
    {
        $tabla = $this->getTable();
        $tabla->getAction('Imprimir_Doc')->visible(true);
        $this->replaceMountedTableAction('Imprimir_Doc');
        $tabla->getAction('Imprimir_Doc')->visible(false);
    }
}
