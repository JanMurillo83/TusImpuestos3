<?php

namespace App\Filament\Clusters\tiadmin\Resources\FacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\FacturasResource;
use App\Http\Controllers\TimbradoController;
use App\Models\Clientes;
use App\Models\CuentasCobrar;
use App\Models\DatosFiscales;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\Facturas;
use App\Models\FacturasPartidas;
use App\Models\Formas;
use App\Models\Metodos;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\SeriesFacturas;
use App\Models\SurtidoInve;
use App\Models\TableSettings;
use App\Models\Team;
use App\Models\Usos;
use Asmit\ResizedColumn\HasResizableColumn;
use Carbon\Carbon;
use CfdiUtils\Cleaner\Cleaner;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PHPMailer\PHPMailer\PHPMailer;
use Spatie\Browsershot\Browsershot;
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
            ->elementId('Imprimir_Doc_P')
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
                ->elementId('Imprimir_Doc_E')
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
                            Grid::make(3)->schema([
                                Select::make('forma')
                                    ->label('Metodo de Pago')
                                    ->options(Formas::all()->pluck('mostrar','clave'))
                                    ->default('PPD')
                                    ->columnSpan(1)->required(),
                                Select::make('metodo')
                                    ->label('Forma de Pago')
                                    ->options(Metodos::all()->pluck('mostrar','clave'))
                                    ->default('99')->required(),
                                Select::make('uso')
                                    ->label('Uso de CFDI')
                                    ->options(Usos::all()->pluck('mostrar','clave'))
                                    ->default('G03')
                                    ->columnSpan(1)->required(),
                            ]),
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
                                    Hidden::make('cantidad_a_facturar'),
                                ]),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('origen_subtotal')
                                ->label('Subtotal:')
                                ->content(fn ($livewire) => '$'.number_format(Cotizaciones::find($livewire->requ)?->subtotal,2)),
                            Placeholder::make('origen_iva')
                                ->label('I.V.A.:')
                                ->content(fn ($livewire) => '$'.number_format(Cotizaciones::find($livewire->requ)?->iva,2)),
                            Placeholder::make('origen_total')
                                ->label('Total:')
                                ->content(fn ($livewire) => '$'.number_format(Cotizaciones::find($livewire->requ)?->total,2)),
                        ]),
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
                                    // Obtener siguiente folio de forma segura
                                    $serieId = intval($get('sel_serie'));
                                    $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

                                    $factura = \App\Models\Facturas::create([
                                        'serie' => $folioData['serie'],
                                        'folio' => $folioData['folio'],
                                        'docto' => $folioData['docto'],
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
                                        'metodo' => $get('metodo') ?? 'PPD',
                                        'forma' => $get('forma') ?? '99',
                                        'uso' => $get('uso') ?? 'G03',
                                        'condiciones' => $cot->condiciones ?? 'CONTADO',
                                    ]);

                                    $subtotal = 0;
                                    $iva = 0;
                                    $retiva = 0;
                                    $retisr = 0;
                                    $ieps = 0;
                                    $total = 0;
                                    $esquema = Esquemasimp::where('id',$cot->esquema)->first();
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
                                            'por_imp1'=>$esquema->iva,
                                            'por_imp2'=>$esquema->retiva,
                                            'por_imp3'=>$esquema->retisr,
                                            'por_imp4'=>$esquema->ieps,
                                            'team_id' => Filament::getTenant()->id,
                                            'cotizacion_partida_id' => $parOriginal->id,
                                        ]);
                                        SurtidoInve::create([
                                            'factura_id' => $factura->id,
                                            'factura_partida_id' => $part_n->id,
                                            'item_id' => $parOriginal->item,
                                            'descr' => $parOriginal->descripcion,
                                            'cant' => $cantFacturar,
                                            'precio_u' => $parOriginal->precio,
                                            'costo_u' => $parOriginal->costo,
                                            'precio_total' => $parOriginal->precio * $parOriginal->cant,
                                            'costo_total' => $parOriginal->costo * $parOriginal->cant,
                                            'team_id' => Filament::getTenant()->id
                                        ]);
                                        $subtotal += $lineSubtotal;
                                        $iva += $lineIva;
                                        $retiva += $lineRetIva;
                                        $retisr += $lineRetIsr;
                                        $ieps += $lineIeps;
                                        $total += $lineTotal;

                                        $parOriginal->update(['pendientes' => max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantFacturar)]);
                                    }

                                    $factura->update([
                                        'subtotal' => $subtotal, 'iva' => $iva, 'retiva' => $retiva,
                                        'retisr' => $retisr, 'ieps' => $ieps, 'total' => $total,
                                        'pendiente_pago' => $total,
                                    ]);

                                    $pendientesTotales = CotizacionesPartidas::where('cotizaciones_id', $cot->id)->sum('pendientes');
                                    $cot->update(['estado' => $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial']);

                                    DB::commit();

                                    //-----------------------------------------------------------
                                    $emp = DatosFiscales::where('team_id', Filament::getTenant()->id)->first();
                                    if ($emp->key != null && $emp->key != '') {
                                        $record = $factura;
                                        $receptor = $record->clie;
                                        $res = app(TimbradoController::class)->TimbrarFactura($record->id, $receptor);
                                        $resultado = json_decode($res);
                                        $codigores = $resultado->codigo;
                                        if ($codigores == "200") {
                                            // El folio ya fue incrementado al obtenerlo con obtenerSiguienteFolio()
                                            $date = Carbon::now();
                                            $facturamodel = Facturas::find($record->id);
                                            $facturamodel->timbrado = 'SI';
                                            $facturamodel->xml = $resultado->cfdi;
                                            $facturamodel->fecha_tim = $date;
                                            $facturamodel->save();
                                            $res2 = app(TimbradoController::class)->actualiza_fac_tim($record->id, $resultado->cfdi, "F");
                                            $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: ' . $res2;
                                            $dias_cr = intval($cliente_d?->dias_credito ?? 0);
                                            $emp = Team::where('id', Filament::getTenant()->id)->first();
                                            $cli = Clientes::where('id', $record->clie)->first();
                                            $archivo_pdf = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.pdf';
                                            $ruta = public_path() . '/TMPCFDI/' . $archivo_pdf;
                                            if (File::exists($ruta)) File::delete($ruta);
                                            $data = ['idorden' => $record->id, 'id_empresa' => Filament::getTenant()->id];
                                            $html = View::make('RepFactura', $data)->render();
                                            Browsershot::html($html)->format('Letter')
                                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                                ->noSandbox()
                                                ->scale(0.8)->savePdf($ruta);
                                            $nombre = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.xml';
                                            $archivo_xml = public_path() . '/TMPCFDI/' . $nombre;
                                            if (File::exists($archivo_xml)) unlink($archivo_xml);
                                            $xml = $resultado->cfdi;
                                            $xml = Cleaner::staticClean($xml);
                                            File::put($archivo_xml, $xml);
                                            //-----------------------------------------------------------
                                            $zip = new \ZipArchive();
                                            $zipPath = public_path() . '/TMPCFDI/';
                                            $zipFileName = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.zip';
                                            $zipFile = $zipPath . $zipFileName;
                                            if ($zip->open(($zipFile), \ZipArchive::CREATE) === true) {
                                                $zip->addFile($archivo_xml, $nombre);
                                                $zip->addFile($ruta, $archivo_pdf);
                                                $zip->close();
                                            } else {
                                                return false;
                                            }
                                            $docto = $record->serie . $record->folio;
                                            //self::EnvioCorreo($record->clie, $ruta, $archivo_xml, $docto, $archivo_pdf, $nombre);
                                            //self::MsjTimbrado($mensaje_graba);
                                            //return response()->download($zipFile);
                                            //-----------------------------------------------------------

                                        } else {
                                            $mensaje_tipo = "2";
                                            $mensaje_graba = $resultado->mensaje;
                                            $record->error_timbrado = $resultado->mensaje;
                                            $record->save();
                                            Notification::make()
                                                ->warning()
                                                ->title('Error al Timbrar el Documento')
                                                ->body($mensaje_graba)
                                                ->persistent()
                                                ->send();
                                        }
                                    }
                                    //-----------------------------------------------------------
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
                                    // Obtener siguiente folio de forma segura
                                    $serieId = intval($get('sel_serie'));
                                    $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

                                    $factura = \App\Models\Facturas::create([
                                        'serie' => $folioData['serie'],
                                        'folio' => $folioData['folio'],
                                        'docto' => $folioData['docto'],
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
                                    // El folio ya fue incrementado al obtenerlo con obtenerSiguienteFolio()
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

    public static function EnvioCorreo($cliente,$filepdf,$filexml,$docto,$nombrepdf,$nombrexml)
    {
        $Cliente = Clientes::where('id',$cliente)->first();
        if($Cliente->correo != null) {
            $mail = new PHPMailer();
            $mail->isSMTP();
            //$mail->SMTPDebug = 2;
            $mail->Host = 'smtp.ionos.mx';
            $mail->Port = 587;
            $mail->AuthType = 'LOGIN';
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Username = 'sistema@app-tusimpuestos.com';
            $mail->Password = '*TusImpuestos2025$*';
            $mail->setFrom('sistema@app-tusimpuestos.com', Filament::getTenant()->name);
            $mail->addAddress($Cliente->correo, $Cliente->nombre);
            $mail->addAttachment($filepdf, $nombrepdf);
            $mail->addAttachment($filexml, $nombrexml);
            $mail->Subject = 'Factura CFDI ' . $docto . ' ' . $Cliente->nombre;
            $mail->msgHTML('<b>Factura CFDI</b>');
            $mail->Body = 'Factura CFDI';
            $mail->send();
            Notification::make()
                ->success()
                ->title('Envio de Correo')
                ->body('Factura Enviada ' . $mail->ErrorInfo)
                ->send();
        }else{
            Notification::make()
                ->warning()
                ->title('Envio de Correo')
                ->body('Cliente sin Correo configurado')
                ->send();
        }
    }

    public static function MsjTimbrado($mensaje_graba)
    {
        Notification::make()
            ->success()
            ->title('Factura Timbrada Correctamente')
            ->body($mensaje_graba)
            ->duration(2000)
            ->send();
    }
}
