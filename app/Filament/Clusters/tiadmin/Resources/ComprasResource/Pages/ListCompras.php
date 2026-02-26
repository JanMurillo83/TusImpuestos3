<?php

namespace App\Filament\Clusters\tiadmin\Resources\ComprasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ComprasResource;
use App\Models\Compras;
use App\Models\DatosFiscales;
use App\Models\Inventario;
use App\Models\Movinventario;
use App\Models\Ordenes;
use App\Models\Proveedores;
use App\Models\Requisiciones;
use App\Models\SeriesFacturas;
use App\Services\CompraInventarioService;
use Asmit\ResizedColumn\HasResizableColumn;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListCompras extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ComprasResource::class;
    public ?int $idorden;
    public ?int $id_empresa;
    public ?int $requ;
    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(true)
                ->margin([0,0,0,2])
                ->content(fn() => view('RepCompra',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->filename(function () {
                    $record = Compras::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$this->id_empresa)->first();
                    $cli = Proveedores::where('id',$record->prov)->first();
                    return $emp->rfc.'_COMPRA_'.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
            Actions\Action::make('Importar Requisición')
                ->icon('fas-file-invoice-dollar')
                ->color(Color::Green)
                ->visible(false)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->mountUsing(function (Form $form,$livewire,Actions\Action $action) {
                    $action->close();
                    $fol = $livewire->requ;
                    $record = Requisiciones::where('id',$fol)->first();
                    //dd($record);
                    $partidas = $record->partidas()
                        ->where(function($q) {
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
                                'cantidad_a_convertir' => $partida->pendientes ?? $partida->cant,
                                'costo' => $partida->costo,
                            ];
                        })->toArray();

                    $form->fill([
                        'partidas' => $partidas,
                    ]);
                })
                ->form([
                    Section::make('Información de la Requisición')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Placeholder::make('origen_folio')
                                        ->label('Folio Requisición')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Requisiciones::where('id',$fol)->first();
                                            return $record->folio;
                                        }),
                                    Placeholder::make('origen_fecha')
                                        ->label('Fecha')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Requisiciones::where('id',$fol)->first();
                                            return $record->fecha;
                                        }),
                                    Placeholder::make('origen_proveedor')
                                        ->label('Proveedor')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Proveedores::where('id',Requisiciones::where('id',$fol)->first()->prov)->first();
                                            return $record->nombre;
                                        }),
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
                                    TextInput::make('cantidad_a_convertir')
                                        ->label('A Convertir')
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
                    Hidden::make('is_vis')->default('SI')
                        ->live(),
                    \Filament\Forms\Components\Actions::make([
                        FormAction::make('Generar Orden')
                            ->visible(function(Get $get){
                                if($get('is_vis') == 'NO') return false;
                                else return true;
                            })
                            ->action(function (array $data,Get $get,$livewire,FormAction $action,Set $set) {
                                $action->close();
                                $req = Requisiciones::where('id',$livewire->requ)->first();

                                $partidasSeleccionadas = collect($get('partidas'))->filter(fn($p) => $p['cantidad_a_convertir'] > 0);

                                if ($partidasSeleccionadas->isEmpty()) {
                                    Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                                    return;
                                }

                                DB::beginTransaction();
                                try {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COMPRAS)
                                        ->first();
                                    if (! $serieRow) {
                                        throw new \Exception('No se encontro una serie de compras configurada.');
                                    }
                                    $folioData = SeriesFacturas::obtenerSiguienteFolio($serieRow->id);

                                    // Crear Recepción (Compra)
                                    $orden = \App\Models\Compras::create([
                                        'serie' => $folioData['serie'],
                                        'folio' => $folioData['folio'],
                                        'docto' => $folioData['docto'],
                                        'fecha' => now()->format('Y-m-d'),
                                        'prov' => $req->prov,
                                        'nombre' => $req->nombre,
                                        'esquema' => $req->esquema,
                                        'subtotal' => 0,
                                        'iva' => 0,
                                        'retiva' => 0,
                                        'retisr' => 0,
                                        'ieps' => 0,
                                        'total' => 0,
                                        'moneda' => $req->moneda,
                                        'tcambio' => $req->tcambio ?? 1,
                                        'observa' => 'Generada desde Orden #'.$req->folio,
                                        'estado' => 'Activa',
                                        'orden' => $req->id,
                                        'orden_id' => $req->id,
                                        'requisicion_id' => $req->requisicion_id,
                                        'team_id' => Filament::getTenant()->id,
                                    ]);

                                    $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                                    foreach ($partidasSeleccionadas as $pData) {
                                        $parOriginal = \App\Models\RequisicionesPartidas::find($pData['partida_id']);
                                        if (!$parOriginal) continue;

                                        $cantConvertir = $pData['cantidad_a_convertir'];
                                        $unit = $parOriginal->costo;

                                        // Calcular proporcionales de impuestos basándose en la cantidad original de la partida de la requisición
                                        $factor = $parOriginal->cant > 0 ? ($cantConvertir / $parOriginal->cant) : 0;

                                        $lineSubtotal = $unit * $cantConvertir;
                                        $lineIva = $parOriginal->iva * $factor;
                                        $lineRetIva = $parOriginal->retiva * $factor;
                                        $lineRetIsr = $parOriginal->retisr * $factor;
                                        $lineIeps = $parOriginal->ieps * $factor;
                                        $lineTotal = $lineSubtotal + $lineIva + $lineIeps - $lineRetIva - $lineRetIsr;

                                        \App\Models\ComprasPartidas::create([
                                            'compras_id' => $orden->id,
                                            'item' => $parOriginal->item,
                                            'descripcion' => $parOriginal->descripcion,
                                            'cant' => $cantConvertir,
                                            'costo' => $unit,
                                            'subtotal' => $lineSubtotal,
                                            'iva' => $lineIva,
                                            'retiva' => $lineRetIva,
                                            'retisr' => $lineRetIsr,
                                            'ieps' => $lineIeps,
                                            'total' => $lineTotal,
                                            'unidad' => $parOriginal->unidad,
                                            'cvesat' => $parOriginal->cvesat,
                                            'prov' => $parOriginal->prov ?? $req->prov,
                                            'observa' => 'Desde Requisición #'.$req->folio.' partida #'.$parOriginal->id,
                                            'team_id' => Filament::getTenant()->id,
                                            'requisicion_partida_id' => $parOriginal->id,
                                        ]);

                                        // Actualizar pendientes en Requisición
                                        $parOriginal->pendientes = max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantConvertir);
                                        $parOriginal->save();

                                        $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva; $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;
                                    }

                                    $orden->update([
                                        'subtotal' => $subtotal,
                                        'iva' => $iva,
                                        'retiva' => $retiva,
                                        'retisr' => $retisr,
                                        'ieps' => $ieps,
                                        'total' => $total,
                                    ]);

                                    // Actualizar estado de la requisición
                                    $quedanPend = \App\Models\RequisicionesPartidas::where('requisiciones_id', $req->id)
                                        ->where(function($q){ $q->whereNull('pendientes')->orWhere('pendientes','>',0); })
                                        ->exists();

                                    $req->estado = $quedanPend ? 'Parcial' : 'Cerrada';
                                    $req->save();

                                    DB::commit();
                                    CompraInventarioService::aplicarEntrada($orden);
                                    $ordenLabel = $orden->docto ?? $orden->folio;
                                    Notification::make()->title('Recepción generada #'.$ordenLabel)->success()->send();
                                    $action->close();
                                    $this->dispatch('close-modal', ['id' => $action->getName()]);
                                    $set('is_vis','NO');
                                    $archivo_pdf = 'COMPRA'.$orden->id.'.pdf';
                                    $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                    if(\File::exists($ruta)) unlink($ruta);
                                    $data = ['idorden'=>$orden->id];
                                    $html = \Illuminate\Support\Facades\View::make('RecepcionCompra',$data)->render();
                                    \Spatie\Browsershot\Browsershot::html($html)->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($ruta);
                                    return response()->download($ruta);
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    Notification::make()->title('Error al generar la Recepción: ' . $e->getMessage())->danger()->send();
                                    $action->close();
                                    $this->dispatch('close-modal', ['id' => $action->getName()]);
                                    $set('is_vis','NO');
                                }
                            }),
                        FormAction::make('Cancelar')
                            ->color(Color::Red)
                            ->action(function (FormAction $action) {
                                $action->close();
                                $this->dispatch('close-modal', ['id' => $action->getName()]);
                            })
                    ])
                ]),
            Actions\Action::make('Importar Orden')
                ->icon('fas-file-invoice-dollar')
                ->color(Color::Green)
                ->visible(false)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->mountUsing(function (Form $form,$livewire,Actions\Action $action) {
                    $action->close();
                    $fol = $livewire->requ;
                    $record = Ordenes::where('id',$fol)->first();
                    //dd($record);
                    $partidas = $record->partidas()
                        ->where(function($q) {
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
                                'cantidad_a_convertir' => $partida->pendientes ?? $partida->cant,
                                'costo' => $partida->costo,
                            ];
                        })->toArray();

                    $form->fill([
                        'partidas' => $partidas,
                    ]);
                })
                ->form([
                    Section::make('Información de la Orden')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Placeholder::make('origen_folio')
                                        ->label('Folio Orden')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Ordenes::where('id',$fol)->first();
                                            return $record->folio;
                                        }),
                                    Placeholder::make('origen_fecha')
                                        ->label('Fecha')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Ordenes::where('id',$fol)->first();
                                            return $record->fecha;
                                        }),
                                    Placeholder::make('origen_proveedor')
                                        ->label('Proveedor')
                                        ->content(function ($livewire){
                                            $fol = $livewire->requ;
                                            $record = Proveedores::where('id',Ordenes::where('id',$fol)->first()->prov)->first();
                                            return $record->nombre;
                                        }),
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
                                    TextInput::make('cantidad_a_convertir')
                                        ->label('A Convertir')
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
                    Hidden::make('is_vis')->default('SI')
                        ->live(),
                    \Filament\Forms\Components\Actions::make([
                        FormAction::make('Generar Recepcion')
                            ->visible(function(Get $get){
                                if($get('is_vis') == 'NO') return false;
                                else return true;
                            })
                            ->action(function (array $data,Get $get,$livewire,FormAction $action,Set $set) {
                                $action->close();
                                $req = Ordenes::where('id',$livewire->requ)->first();

                                $partidasSeleccionadas = collect($get('partidas'))->filter(fn($p) => $p['cantidad_a_convertir'] > 0);

                                if ($partidasSeleccionadas->isEmpty()) {
                                    Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                                    return;
                                }

                                DB::beginTransaction();
                                try {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COMPRAS)
                                        ->first();
                                    if (! $serieRow) {
                                        throw new \Exception('No se encontro una serie de compras configurada.');
                                    }
                                    $folioData = SeriesFacturas::obtenerSiguienteFolio($serieRow->id);

                                    // Crear Orden
                                    $orden = \App\Models\Compras::create([
                                        'serie' => $folioData['serie'],
                                        'folio' => $folioData['folio'],
                                        'docto' => $folioData['docto'],
                                        'fecha' => now()->format('Y-m-d'),
                                        'prov' => $req->prov,
                                        'nombre' => $req->nombre,
                                        'esquema' => $req->esquema,
                                        'subtotal' => 0,
                                        'iva' => 0,
                                        'retiva' => 0,
                                        'retisr' => 0,
                                        'ieps' => 0,
                                        'total' => 0,
                                        'moneda' => $req->moneda,
                                        'tcambio' => $req->tcambio ?? 1,
                                        'observa' => 'Generada desde Requisición #'.$req->folio,
                                        'estado' => 'Activa',
                                        'requisicion_id' => $req->id,
                                        'team_id' => Filament::getTenant()->id,
                                    ]);

                                    $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                                    foreach ($partidasSeleccionadas as $pData) {
                                        $parOriginal = \App\Models\OrdenesPartidas::find($pData['partida_id']);
                                        if (!$parOriginal) continue;

                                        $cantConvertir = $pData['cantidad_a_convertir'];
                                        $unit = $parOriginal->costo;

                                        // Calcular proporcionales de impuestos basándose en la cantidad original de la partida de la requisición
                                        $factor = $parOriginal->cant > 0 ? ($cantConvertir / $parOriginal->cant) : 0;

                                        $lineSubtotal = $unit * $cantConvertir;
                                        $lineIva = $parOriginal->iva * $factor;
                                        $lineRetIva = $parOriginal->retiva * $factor;
                                        $lineRetIsr = $parOriginal->retisr * $factor;
                                        $lineIeps = $parOriginal->ieps * $factor;
                                        $lineTotal = $lineSubtotal + $lineIva + $lineIeps - $lineRetIva - $lineRetIsr;

                                        \App\Models\ComprasPartidas::create([
                                            'compras_id' => $orden->id,
                                            'item' => $parOriginal->item,
                                            'descripcion' => $parOriginal->descripcion,
                                            'cant' => $cantConvertir,
                                            'costo' => $unit,
                                            'subtotal' => $lineSubtotal,
                                            'iva' => $lineIva,
                                            'retiva' => $lineRetIva,
                                            'retisr' => $lineRetIsr,
                                            'ieps' => $lineIeps,
                                            'total' => $lineTotal,
                                            'unidad' => $parOriginal->unidad,
                                            'cvesat' => $parOriginal->cvesat,
                                            'prov' => $parOriginal->prov ?? $req->prov,
                                            'observa' => 'Desde Orden #'.$req->folio.' partida #'.$parOriginal->id,
                                            'team_id' => Filament::getTenant()->id,
                                            'orden_partida_id' => $parOriginal->id,
                                        ]);
                                        // Actualizar pendientes en Orden
                                        $parOriginal->pendientes = max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantConvertir);
                                        $parOriginal->save();

                                        $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva; $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;
                                    }

                                    $orden->update([
                                        'subtotal' => $subtotal,
                                        'iva' => $iva,
                                        'retiva' => $retiva,
                                        'retisr' => $retisr,
                                        'ieps' => $ieps,
                                        'total' => $total,
                                    ]);

                                    // Actualizar estado de la orden
                                    $quedanPend = \App\Models\OrdenesPartidas::where('ordenes_id', $req->id)
                                        ->where(function($q){ $q->whereNull('pendientes')->orWhere('pendientes','>',0); })
                                        ->exists();

                                    $req->estado = $quedanPend ? 'Parcial' : 'Comprada';
                                    $req->compra = $orden->folio;
                                    $req->save();

                                    DB::commit();
                                    CompraInventarioService::aplicarEntrada($orden);
                                    $ordenLabel = $orden->docto ?? $orden->folio;
                                    Notification::make()->title('Recepción generada #'.$ordenLabel)->success()->send();
                                    $action->close();
                                    $this->dispatch('close-modal', ['id' => $action->getName()]);
                                    $set('is_vis','NO');
                                    $archivo_pdf = 'COMPRA'.$orden->id.'.pdf';
                                    $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                    if(\File::exists($ruta)) unlink($ruta);
                                    $data = ['idorden'=>$orden->id];
                                    $html = \Illuminate\Support\Facades\View::make('RecepcionCompra',$data)->render();
                                    \Spatie\Browsershot\Browsershot::html($html)->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($ruta);
                                    return response()->download($ruta);
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    Notification::make()->title('Error al generar la Recepción: ' . $e->getMessage())->danger()->send();
                                    $action->close();
                                    $this->dispatch('close-modal', ['id' => $action->getName()]);
                                    $set('is_vis','NO');
                                }
                            }),
                        FormAction::make('Cancelar')
                            ->color(Color::Red)
                            ->action(function (FormAction $action) {
                                $action->close();
                                $this->dispatch('close-modal', ['id' => $action->getName()]);
                            })
                    ])
                ])
        ];
    }
}
