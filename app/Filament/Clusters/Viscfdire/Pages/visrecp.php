<?php

namespace App\Filament\Clusters\Viscfdire\Pages;

use App\Filament\Clusters\Viscfdire;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\Terceros;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class visrecp extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $cluster = Viscfdire::class;
    protected static ?string $title = 'Comprobantes de Pago';
    protected static string $view = 'filament.clusters.viscfdire.pages.visrecp';
    public function table(Table $table): Table
    {
        return $table
        ->query(
            Almacencfdis::where('team_id',Filament::getTenant()->id)
            ->where('xml_type','Recibidos')
            ->where('TipoDeComprobante','P')
             )
        ->columns([
            TextColumn::make('id')
                ->label('#')
                ->rowIndex()
                ->sortable(),
            TextColumn::make('Fecha')
                ->searchable()
                ->sortable()
                ->date('d-m-Y'),
            TextColumn::make('Serie')
                ->searchable()
                ->sortable(),
            TextColumn::make('Folio')
                ->searchable()
                ->sortable(),
            TextColumn::make('Moneda')
                ->searchable()
                ->sortable(),
            TextColumn::make('TipoDeComprobante')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('Receptor_Rfc')
                ->label('RFC Receptor')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('Receptor_Nombre')
                ->label('Nombre Receptor')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('Emisor_Rfc')
                ->label('RFC Emisor')
                ->searchable()
                ->sortable(),
            TextColumn::make('Emisor_Nombre')
                ->label('Nombre Emisor')
                ->searchable()
                ->sortable()
                ->limit(20),
            TextColumn::make('Moneda')
                ->label('Moneda')
                ->searchable()
                ->sortable(),
            TextColumn::make('TipoCambio')
                ->label('T.C.')
                ->sortable()
                ->numeric()
                ->formatStateUsing(function (string $state) {
                    if($state <= 0) $state = 1;
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
            TextColumn::make('Total')
                ->sortable()
                ->numeric()
                ->formatStateUsing(function (string $state) {
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
            TextColumn::make('notas')
                ->label('Referencia')
                ->searchable()
                ->sortable(),
            TextColumn::make('used')
                ->label('Asociado')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('UUID')
                ->label('UUID')
                ->searchable()
                ->sortable(),
            TextColumn::make('MetodoPago')
                ->label('Forma de Pago')
                ->searchable()
                ->sortable(),
            TextColumn::make('xml_type')
                ->label('Tipo')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('ejercicio')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('periodo')
                ->numeric()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->striped()->defaultPaginationPageOption(8)
                ->paginated([8, 'all'])
                ->filters([
                    Filter::make('created_at')
                    ->form([
                        DatePicker::make('Fecha_Inicial')
                        ->default(function(){
                            $ldom = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo;
                            $Fecha_Inicial = Carbon::make('first day of'.$ldom);
                            return $Fecha_Inicial->format('Y-m-d');
                        }),
                        DatePicker::make('Fecha_Final')
                        ->default(function(){
                            $ldom = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo;
                            $Fecha_Inicial = Carbon::make('last day of'.$ldom);
                            return $Fecha_Inicial->format('Y-m-d');
                        }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['Fecha_Inicial'],
                                fn (Builder $query, $date): Builder => $query->whereDate('Fecha', '>=', $date),
                            )
                            ->when(
                                $data['Fecha_Final'],
                                fn (Builder $query, $date): Builder => $query->whereDate('Fecha', '<=', $date),);
                            })
                ],layout: FiltersLayout::Modal)
                ->filtersTriggerAction(
                    fn (Action $action) => $action
                        ->button()
                        ->label('Cambiar Periodo'),
                )
                ->deferFilters()
                ->defaultSort('Fecha', 'asc')
                ->recordAction('Notas')
            ->actions([
                EditAction::make('Notas')
                        ->label('')
                        ->icon(null)
                        ->modalHeading('Referecnia')
                        ->form([
                            Textarea::make('notas')
                            ->label('Referencia')
                    ])
                    ->action(function(Model $record,$data){
                        $record['notas'] = $data['notas'];
                        $record->save();
                    }),
                ActionGroup::make([
                ViewAction::make()
                ->label('Expediente')
                ->infolist(function($infolist,$record){
                    $pols = DB::table('cat_polizas')->where('uuid',$record->UUID)->get();
                    $nopols = count($pols);
                    if($nopols == 0)
                    {
                        return $infolist
                        ->schema([
                            TextEntry::make('No')
                            ->label('No Existen Polizas para este UUID'),
                        ]);
                    }
                    else{

                        $encabezado []= ['UUID'=>$record->UUID,
                        'Fecha'=>$record->Fecha,'Emisor'=>$record->Emisor_Rfc,
                        'Receptor'=>$record->Receptor_Rfc,'Polizas'=>$pols];
                        //dd($encabezado);
                        return $infolist
                        ->state($encabezado[0])
                        ->schema([
                            Section::make()
                            ->schema([
                            TextEntry::make('UUID')
                            ->columnSpan(2)->label('UUID'),
                            TextEntry::make('Fecha'),
                            TextEntry::make('Emisor'),
                            TextEntry::make('Receptor')])->columns(5),
                            Section::make()
                            ->schema([
                                RepeatableEntry::make('Polizas')
                                ->schema([
                                    TextEntry::make('fecha'),
                                    TextEntry::make('tipo'),
                                    TextEntry::make('folio')
                                ])->columns(3)
                            ])
                        ])->columns(5);
                }
                })
                ])
            ])->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
            ]);
    }

   /* public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('UUID'),
                TextEntry::make('ejercicio'),
                TextEntry::make('periodo'),
            ]);
    }*/

    public static function contabiliza_r($record,$data)
    {
        $tipoxml = $record['xml_type'];
        $tipocom = $record['TipoDeComprobante'];
        $rfc_rec = $record['Receptor_Rfc'];
        $rfc_emi = $record['Emisor_Rfc'];
        $nom_rec = $record['Receptor_Nombre'];
        $nom_emi = $record['Emisor_Nombre'];
        $subtotal = $record['SubTotal'];
        $iva = $record['TotalImpuestosTrasladados'];
        $total = $record['Total'];
        $tipoc = $record['TipoCambio'];
        $metodo = $record['MetodoPago'];
        $serie = $record['Serie'];
        $folio = $record['Folio'];
        $uuid = $record['UUID'];
        $cfperiodo = $record['periodo'];
        $cfejercicio = $record['ejercicio'];
        $cffecha1 = $record['Fecha'];
        list($cffecha,$cfhora) = explode('T',$cffecha1);
        $forma = $data['forma'];
        $ctagas = $data['detallegas'];
        if($tipoxml == 'Recibidos'&&$tipocom == 'I')
        {
            $existe = CatCuentas::where('nombre',$nom_rec)->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first();
            if($existe)
            {
                $ctaclie = $existe->codigo;
            }
            else
            {
                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20101000')->max('codigo')) + 1;
                CatCuentas::firstOrCreate([
                    'nombre' =>  $nom_rec,
                    'team_id' => Filament::getTenant()->id,
                    'codigo'=>$nuecta,
                    'acumula'=>'20101000',
                    'tipo'=>'D',
                    'naturaleza'=>'D',
                ]);
                Terceros::create([
                    'rfc'=>$rfc_rec,
                    'nombre'=>$nom_rec,
                    'tipo'=>'Proveedor',
                    'cuenta'=>$nuecta,
                    'telefono'=>'',
                    'correo'=>'',
                    'contacto'=>'',
                    'tax_id'=>$rfc_emi,
                    'team_id'=>Filament::getTenant()->id
                ]);
                $ctaclie = $nuecta;
            }
            $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PG')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
            Almacencfdis::where('id',$record['id'])->update([
                'metodo'=>$forma
            ]);
            $poliza = CatPolizas::create([
                'tipo'=>'PG',
                'folio'=>$nopoliza,
                'fecha'=>$cffecha,
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargos'=>$total,
                'abonos'=>$total,
                'periodo'=>$cfperiodo,
                'ejercicio'=>$cfejercicio,
                'referencia'=>$serie.$folio,
                'uuid'=>$uuid,
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ctaclie,
                'cuenta'=>$nom_rec,
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargo'=>0,
                'abono'=>$total,
                'factura'=>$uuid,
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11901000',
                'cuenta'=>'IVA trasladado no cobrado',
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargo'=>$iva,
                'abono'=>0,
                'factura'=>$uuid,
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ctagas,
                'cuenta'=>'Ventas',
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargo'=>$subtotal,
                'abono'=>0,
                'factura'=>$uuid,
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            DB::table('almacencfdis')->where('id',$record->id)->update([
                'used'=> 'SI',
                'metodo'=>$forma
            ]);
            Notification::make()
                ->title('Contabilizar')
                ->body('Poliza '.$nopoliza.' Generada Correctamente')
                ->success()
                ->send();
        }
    }
}
