<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Operscfdi;
use App\Filament\Resources\AlmacencfdisResource\Pages;
use App\Filament\Resources\AlmacencfdisResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\CatCuentas;
use App\Models\Terceros;
use App\Models\CatPolizas;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\MaxWidth;
use App\Http\Controllers\Funciones;
use App\Models\Auxiliares;
use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;

class AlmacencfdisResource extends Resource
{
    protected static ?string $model = Almacencfdis::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $pluralLabel = 'Visor de Documentos Digitales';
    protected static ?string $label = 'CFDI';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Serie')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Folio')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Fecha')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Moneda')
                    ->maxLength(255),
                Forms\Components\TextInput::make('TipoDeComprobante')
                    ->maxLength(255),
                Forms\Components\TextInput::make('MetodoPago')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_Rfc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_Nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_RegimenFiscal')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_Rfc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_Nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_RegimenFiscal')
                    ->maxLength(255),
                Forms\Components\TextInput::make('UUID')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Total')
                    ->numeric(),
                Forms\Components\TextInput::make('SubTotal')
                    ->numeric(),
                Forms\Components\TextInput::make('TipoCambio')
                    ->numeric(),
                Forms\Components\TextInput::make('TotalImpuestosTrasladados')
                    ->numeric(),
                Forms\Components\TextInput::make('TotalImpuestosRetenidos')
                    ->numeric(),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('user_tax')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('used')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('xml_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('metodo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ejercicio')
                    ->numeric(),
                Forms\Components\TextInput::make('periodo')
                    ->numeric(),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Serie')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Folio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Fecha')
                    ->searchable()
                    ->sortable()
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('Moneda')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('TipoDeComprobante')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Receptor_Rfc')
                    ->label('RFC Receptor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Receptor_Nombre')
                    ->label('Nombre Receptor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('UUID')
                    ->label('UUID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Total')
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('used')
                    ->label('Utilizado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
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
                SelectFilter::make('xml_type')
                ->label('Tipo de CFDI')
                ->options(['Emitidos'=>'Emitidos','Recibidos'=>'Recibidos'])
                //->query(fn (Builder $query): Builder => $query->where('xml_type', 'Emitidos'))
                ->attribute('xml_type'),
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
                Action::make('ContabilizarE')
                    ->label('')
                    ->visible(fn ($record) => $record->xml_type == 'Emitidos')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->form([
                        Forms\Components\Select::make('forma')
                            ->label('Forma de Pago')
                            ->options([
                                'Bancario'=>'Cuentas por Cobrar',
                                'Efectivo'=>'Efectivo'
                            ])
                            ->default('Bancario')
                            ->disabled()
                            ->required()
                    ])
                    ->action(function(Model $record,$data){
                        Self::contabiliza_e($record,$data);
                    }),
                    Action::make('ContabilizarR')
                    ->label('')
                    ->visible(fn ($record) => $record->xml_type == 'Recibidos')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->form([
                        Forms\Components\Select::make('rubrogas')
                            ->label('Rubro del Gasto')
                            ->required()
                            ->live()
                            ->options([
                               '50100000' => 'Costo de Ventas',
                               '60200000' => 'Gastos de Venta',
                               '60300000' => 'Gastos de Administracion',
                               '70100000' => 'Gastos Financieros',
                               '70200000' => 'Productos Financieros'
                            ]),
                        Forms\Components\Select::make('detallegas')
                            ->label('Rubro del Gasto')
                            ->required()
                            ->options(function(Get $get) {
                                return
                                CatCuentas::where('acumula',$get('rubrogas'))->pluck('nombre','codigo');
                            }),
                        Forms\Components\Select::make('forma')
                            ->label('Forma de Pago')
                            ->options([
                                'Bancario'=>'Movimiento Bancario',
                                'Efectivo'=>'Efectivo'
                            ])
                            ->required()
                    ])
                    ->action(function(Model $record,$data){
                        Self::contabiliza_r($record,$data);
                    }),
            ])
            ->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Action::make('Contabilizar')
                    ->label('Contabilizar')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            //'index' => Pages\ListAlmacencfdis::route('/'),
            'registro' => Pages\Cfdiregistro::route('/registro'),
            //'cfdi1'=> Pages\CfdiRec::route('/')
            //'create' => Pages\CreateAlmacencfdis::route('/create'),
            //'edit' => Pages\EditAlmacencfdis::route('/{record}/edit'),
        ];
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
        //$forma = $data['forma'];
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
                'concepto'=>$nom_rec,
                'cargos'=>$total,
                'abonos'=>$total,
                'periodo'=>$cfperiodo,
                'ejercicio'=>$cfejercicio,
                'referencia'=>$serie.$folio,
                'uuid'=>$serie.$folio,
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id
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
                'concepto'=>$nom_rec,
                'cargo'=>0,
                'abono'=>$subtotal,
                'factura'=>$serie.$folio,
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            Notification::make()
                ->title('Contabilizar')
                ->body('Poliza '.$nopoliza.' Generada Correctamente')
                ->success()
                ->send();
        }

    }
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
                'concepto'=>$nom_rec,
                'cargos'=>$total,
                'abonos'=>$total,
                'periodo'=>$cfperiodo,
                'ejercicio'=>$cfejercicio,
                'referencia'=>$serie.$folio,
                'uuid'=>$serie.$folio,
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ctaclie,
                'cuenta'=>$nom_rec,
                'concepto'=>$nom_rec,
                'cargo'=>0,
                'abono'=>$total,
                'factura'=>$serie.$folio,
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
                'concepto'=>$nom_rec,
                'cargo'=>$iva,
                'abono'=>0,
                'factura'=>$serie.$folio,
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
                'concepto'=>$nom_rec,
                'cargo'=>$subtotal,
                'abono'=>0,
                'factura'=>$serie.$folio,
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            Notification::make()
                ->title('Contabilizar')
                ->body('Poliza '.$nopoliza.' Generada Correctamente')
                ->success()
                ->send();
        }
    }
}

