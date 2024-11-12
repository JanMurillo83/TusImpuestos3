<?php

namespace App\Filament\Clusters\Viscfdi\Pages;

use App\Filament\Clusters\Viscfdi;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\Terceros;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
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
use Filament\Tables\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class visemir extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $cluster = Viscfdi::class;
    protected static ?string $title = 'Recibos de Nomina';
    protected static string $view = 'filament.clusters.viscfdi.pages.visemir';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Almacencfdis::where('team_id',Filament::getTenant()->id)
                ->where('xml_type','Emitidos')
                ->where('TipoDeComprobante','N')
                ->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio)
                ->orderBy('Fecha', 'ASC')
                )
            ->columns([
                TextColumn::make('Serie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Folio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Fecha')
                    ->searchable()
                    ->sortable()
                    ->date('d-m-Y'),
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
                    ->sortable(),
                TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('UUID')
                    ->label('UUID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Total')
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                TextColumn::make('used')
                    ->label('Utilizado')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('xml_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ejercicio')
                    ->sortable(),
                TextColumn::make('periodo')
                    ->numeric()
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('ejercicio')
                ->options(['2020'=>'2020','2021'=>'2021','2022'=>'2022','2023'=>'2023','2024'=>'2024','2025'=>'2025','2026'=>'2026'])
                ->attribute('ejercicio'),
                SelectFilter::make('periodo')
                ->options(['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio','7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'])
                ->attribute('periodo'),
                Filter::make('Fecha CFDI')
                ->form([
                    DatePicker::make('fecha_i')
                        ->label('F.Inicial'),
                    DatePicker::make('fecha_f')
                        ->label('F.Final')

                ])->columnSpan(2)->columns(2)
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['fecha_i']&&$data['fecha_f'],
                            fn (Builder $query,$date): Builder => $query->whereDate('Fecha','>=', $data['fecha_i'])->whereDate('Fecha','<=',$data['fecha_f'])
                        );
                })
            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(5)
            ->actions([
                ViewAction::make()
                ->label('Expediente')
                ->icon('fas-folder-tree')
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
            ])->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
               /* BulkActionGroup::make([
                    Action::make('ContabilizarE')
                    ->label('Contabilizar')
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
                            Self::contabiliza_r($record,$data);
                    })
                ])*/
            ]);
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
        if($tipoxml == 'Emitidos'&&$tipocom == 'I')
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
                    'naturaleza'=>'A',
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
                'cargo'=>$total,
                'abono'=>0,
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
                'codigo'=>'20901000',
                'cuenta'=>'IVA trasladado no cobrado',
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargo'=>0,
                'abono'=>$iva,
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
                'codigo'=>'40101000',
                'cuenta'=>'Ventas',
                'concepto'=>$nom_rec.' '.$serie.$folio,
                'cargo'=>0,
                'abono'=>$subtotal,
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
