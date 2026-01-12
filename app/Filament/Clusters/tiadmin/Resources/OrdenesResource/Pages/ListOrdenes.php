<?php

namespace App\Filament\Clusters\tiadmin\Resources\OrdenesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\OrdenesResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Ordenes;
use App\Models\Proveedores;
use App\Models\Requisiciones;
use Asmit\ResizedColumn\HasResizableColumn;
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
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListOrdenes extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = OrdenesResource::class;
    public ?int $idorden;
    public ?int $id_empresa;
    public ?int $requ;
    protected function getHeaderActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(true)
                ->margin([0,0,0,2])
                ->content(fn() => view('RepOrden',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->filename(function () {
                    $record = Ordenes::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$this->id_empresa)->first();
                    $cli = Proveedores::where('id',$record->prov)->first();
                    return $emp->rfc.'_ORDENCOMPRA_'.$record->folio.'_'.$cli->rfc.'.pdf';
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
                                // Crear Orden
                                $orden = \App\Models\Ordenes::create([
                                    'folio' => (\App\Models\Ordenes::where('team_id', Filament::getTenant()->id)->max('folio') ?? 0) + 1,
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

                                    \App\Models\OrdenesPartidas::create([
                                        'ordenes_id' => $orden->id,
                                        'item' => $parOriginal->item,
                                        'descripcion' => $parOriginal->descripcion,
                                        'cant' => $cantConvertir,
                                        'pendientes' => $cantConvertir, // Para la orden, todo está pendiente de recibir en compra
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
                                Notification::make()->title('Orden generada #'.$orden->folio)->success()->send();
                                $action->close();
                                $this->dispatch('close-modal', ['id' => $action->getName()]);
                                $set('is_vis','NO');
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Notification::make()->title('Error al generar la orden: ' . $e->getMessage())->danger()->send();
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
