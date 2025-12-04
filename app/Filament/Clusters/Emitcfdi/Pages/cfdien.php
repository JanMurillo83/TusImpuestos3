<?php

namespace App\Filament\Clusters\Emitcfdi\Pages;

use App\Filament\Clusters\Emitcfdi;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
use App\Models\Terceros;
use Asmit\ResizedColumn\HasResizableColumn;
use Carbon\Carbon;
use CfdiUtils\Cleaner\Cleaner;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use stdClass;

class cfdien extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $cluster = Emitcfdi::class;
    protected static ?string $title = 'Notas de Credito Emitidas';
    protected static string $view = 'filament.clusters.emitcfdi.pages.cfdien';
    protected static ?string $headerActionsposition = 'bottom';
    public ?Date $Fecha_Inicial = null;
    public ?Date $Fecha_Final = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(Almacencfdis::query())
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('team_id',Filament::getTenant()->id)
                    ->where('xml_type','Emitidos')
                    ->where('TipoDeComprobante','E')
                    ->where('used','NO');
            })
            ->columns([
                TextColumn::make('Fecha')
                    ->searchable()
                    ->sortable()
                    ->date('d-m-Y'),
                TextColumn::make('Serie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Folio')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $truncatedValue = Str::limit($state, 10);
                        return new HtmlString("<span title='{$state}'>{$truncatedValue}</span>");
                    })
                    ->action(Action::make('Folio')->form([
                        TextInput::make('Folio')
                            ->hiddenLabel()->readOnly()
                            ->default(function($record){
                                return $record->Folio;
                            })
                    ])),
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
                    ->sortable(),
                TextColumn::make('Receptor_Nombre')
                    ->label('Nombre Receptor')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Moneda')
                    ->label('Moneda')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('TipoCambio')
                    ->label('T.C.')
                    ->sortable()
                    ->numeric(decimalPlaces: 4)
                    ->formatStateUsing(function (string $state) {
                        if($state <= 0) $state = 1;
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 4);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                TextColumn::make('Total')
                    ->sortable()
                    ->numeric()->searchable()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                TextColumn::make('used')
                    ->label('Asociado')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('UUID')
                    ->label('UUID')
                    ->formatStateUsing(function ($state) {
                        $truncatedValue = Str::limit($state, 10);
                        return new HtmlString("<span title='{$state}'>{$truncatedValue}</span>");
                    })
                    ->action(Action::make('UUID')->form([
                        TextInput::make('UUID')
                            ->hiddenLabel()->readOnly()
                            ->default(function($record){
                                return $record->UUID;
                            })
                    ])),
                TextColumn::make('MetodoPago')
                    ->label('M. Pago')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('FormaPago')
                    ->label('F. Pago')
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notas')
                    ->label('Refer.')
                    ->searchable()
                    ->sortable(),
                ])
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
                })->visible(function(){
                        $team = Filament::getTenant()->id;
                        $periodo = Filament::getTenant()->periodo;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                        {
                            return true;
                        }
                        else{
                            $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                            if($estado == 1) return true;
                            else return false;
                        }
                    }),
                Action::make('ContabilizarE')
                ->label('')
                    ->visible(function(){
                        $team = Filament::getTenant()->id;
                        $periodo = Filament::getTenant()->periodo;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                        {
                            return true;
                        }
                        else{
                            $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                            if($estado == 1) return true;
                            else return false;
                        }
                    })
                ->tooltip('Contabilizar')
                ->icon('fas-scale-balanced')
                ->modalWidth(MaxWidth::ExtraSmall)
                ->form([
                    Select::make('forma')
                        ->label('Forma de Pago')
                        ->options([
                            'Bancario'=>'Cuentas por Cobrar',
                            'Efectivo'=>'Efectivo'
                        ])
                        ->default('Bancario')
                        ->disabled()
                        ->required()
                ])->action(function(Model $record,$data){
                        Self::contabiliza_e($record,$data);
                }),
                Action::make('Descarga XML')
                    ->label('Descarga XML')
                    ->icon('fas-download')
                    ->action(function($record){
                        $nombre = $record->Receptor_Rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$record->Emisor_Rfc.'.xml';
                        $archivo = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                        if(File::exists($archivo)) unlink($archivo);
                        $xml = $record->content;
                        $xml = Cleaner::staticClean($xml);
                        File::put($archivo,$xml);
                        $ruta = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                        return response()->download($ruta);
                    })
            ])->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
                BulkAction::make('multi_Contabilizar')
                ->label('')
                ->tooltip('Contabilizar')
                ->icon('fas-scale-balanced')
                ->modalWidth(MaxWidth::ExtraSmall)
                ->form([
                    Select::make('forma')
                        ->label('Forma de Pago')
                        ->options([
                            'Bancario'=>'Cuentas por Cobrar',
                            'Efectivo'=>'Efectivo'
                        ])
                        ->default('Bancario')
                        ->disabled()
                        ->required()
                ])
                ->action(function(Collection $records,array $data,$livewire){
                    foreach($records as $record){
                        Self::contabiliza_e($record,$data,$livewire);
                    }
                })

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
                ->defaultSort('Fecha', 'asc');
    }

    public static function contabiliza_e($record,$data)
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
        //dd($cffecha1);
        list($cffecha,$cfhora) = explode('T',$cffecha1);
        $forma = 'CXC';
        if($tipoxml == 'Emitidos'&&$tipocom == 'E')
        {
            $existe = CatCuentas::where('nombre',$nom_rec)->where('acumula','10501000')->where('team_id',Filament::getTenant()->id)->first();
            if($existe)
            {
                $ctaclie = $existe->codigo;
            }
            else
            {
                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','10501000')->max('codigo')) + 1;
                CatCuentas::firstOrCreate([
                    'nombre' =>  $nom_rec,
                    'team_id' => Filament::getTenant()->id,
                    'codigo'=>$nuecta,
                    'acumula'=>'10501000',
                    'tipo'=>'D',
                    'naturaleza'=>'D',
                ]);
                Terceros::create([
                    'rfc'=>$rfc_rec,
                    'nombre'=>$nom_rec,
                    'tipo'=>'Cliente',
                    'cuenta'=>$nuecta,
                    'telefono'=>'',
                    'correo'=>'',
                    'contacto'=>'',
                    'tax_id'=>$rfc_emi,
                    'team_id'=>Filament::getTenant()->id
                ]);
                $ctaclie = $nuecta;
            }
            $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PV')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
            Almacencfdis::where('id',$record['id'])->update([
                'metodo'=>'Bancario'
            ]);
            $poliza = CatPolizas::create([
                'tipo'=>'PV',
                'folio'=>$nopoliza,
                'fecha'=>$cffecha,
                'concepto'=>$nom_rec,
                'cargos'=>$total,
                'abonos'=>$total,
                'periodo'=>$cfperiodo,
                'ejercicio'=>$cfejercicio,
                'referencia'=>$serie.$folio,
                'uuid'=>$uuid,
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id,
                'idcfdi'=>$record->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ctaclie,
                'cuenta'=>$nom_rec,
                'concepto'=>$nom_rec,
                'cargo'=>$total,
                'abono'=>0,
                'factura'=>$serie.$folio,
                'nopartida'=>1,
                'uuid'=>$uuid,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'40101000',
                'cuenta'=>'Ventas',
                'concepto'=>$nom_rec,
                'cargo'=>0,
                'abono'=>$subtotal,
                'factura'=>$serie.$folio,
                'nopartida'=>2,
                'uuid'=>$uuid,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'20901000',
                'cuenta'=>'IVA trasladado no cobrado',
                'concepto'=>$nom_rec,
                'cargo'=>0,
                'abono'=>$iva,
                'factura'=>$serie.$folio,
                'nopartida'=>3,
                'uuid'=>$uuid,
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
