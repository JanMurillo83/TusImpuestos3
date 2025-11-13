<?php

namespace App\Filament\Clusters\Rececfdi\Pages;

use App\Filament\Clusters\Rececfdi;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\Clientes;
use App\Models\ContaPeriodos;
use App\Models\DatosFiscales;
use App\Models\Proveedores;
use App\Models\Regimenes;
use App\Models\Terceros;
use App\Models\CuentasPagar;
use Asmit\ResizedColumn\HasResizableColumn;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use CfdiUtils\Cleaner\Cleaner;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
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
use Filament\Tables\Actions\ActionGroup;
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
use stdClass;

class cfdiri extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $cluster = Rececfdi::class;
    protected static ?string $title = 'Facturas';
    protected static string $view = 'filament.clusters.rececfdi.pages.cfdiri';
    protected static ?string $headerActionsposition = 'bottom';
    public ?Date $Fecha_Inicial = null;
    public ?Date $Fecha_Final = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(Almacencfdis::query())
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('team_id',Filament::getTenant()->id)
                    ->where('xml_type','Recibidos')
                    ->where('TipoDeComprobante','I')
                    ->where('used','NO');
            })
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
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 4);
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
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ->recordAction('Notas')
            ->actions([
                ActionGroup::make([
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
                Action::make('ContabilizarR')
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
                        TextInput::make('dias_cred')
                        ->label('Dias de Crédito')
                        ->numeric()->default(60),
                        Select::make('rubrogas')
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
                        Select::make('detallegas')
                            ->label('Rubro del Gasto')
                            ->required()
                            ->options(function(Get $get) {
                                return
                                CatCuentas::where('team_id',Filament::getTenant()->id)->where('acumula',$get('rubrogas'))->pluck('nombre','codigo');
                            }),
                        Select::make('forma')
                            ->label('Forma de Pago')
                            ->default('CXP')
                            ->options([
                                'CXP'=>'Cuenta por Pagar',
                                'TER'=>'Pagado por Tercero'
                            ])
                            ->live(onBlur: true)
                            ->required(),
                        Select::make('Tercero')
                            ->searchable()
                            ->visible(function(Get $get){
                                if($get('forma')== 'TER') return true;
                                else return false;
                            })
                            ->required(function(Get $get){
                                if($get('forma')== 'TER') return true;
                                else return false;
                            })
                            ->options(Terceros::select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
                            ->createOptionForm(function($form){
                                return $form
                                    ->schema([
                                        TextInput::make('rfc')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(3),
                                        TextInput::make('tipo')
                                            ->label('Tipo de Tercero')
                                            ->default('Acreedor')
                                            ->readOnly(),
                                        TextInput::make('cuenta')
                                            ->required()
                                            ->maxLength(255)
                                            ->readOnly()
                                            ->default(function(){
                                                $nuecta = 20501000;
                                                $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->get() ?? 0);
                                                if($rg > 0)
                                                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->max('codigo')) + 1000;
                                                return $nuecta;
                                            }),
                                        TextInput::make('telefono')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('correo')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('contacto')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('regimen')
                                            ->searchable()
                                            ->label('Regimen Fiscal')
                                            ->columnSpan(2)
                                            ->options(Regimenes::all()->pluck('mostrar','clave')),
                                        Hidden::make('tax_id')
                                            ->default(Filament::getTenant()->taxid),
                                        Hidden::make('team_id')
                                            ->default(Filament::getTenant()->id),
                                        TextInput::make('codigopos')
                                            ->label('Codigo Postal')
                                            ->required()
                                            ->maxLength(255),
                                    ])->columns(4);
                            })
                            ->createOptionUsing(function(array $data){
                                $recor = DB::table('terceros')->insertGetId($data);
                                DB::table('cat_cuentas')->insert([
                                    'nombre' =>  $data['nombre'],
                                    'team_id' => Filament::getTenant()->id,
                                    'codigo'=>$data['cuenta'],
                                    'acumula'=>'20500000',
                                    'tipo'=>'D',
                                    'naturaleza'=>'A',
                                ]);
                                $rec = Terceros::where('id',$recor)->get()[0];
                                return $rec->nombre.'|'.$rec->cuenta;
                            })
                    ])
                    ->action(function(Model $record,$data,$livewire){
                        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PG')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                        self::contabiliza_r($record,$data,$livewire,$nopoliza);
                    })->label('Contabilizar'),
                Action::make('ver_xml')->icon('fas-eye')
                    ->label('Consultar XML')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->form(function ($record,$form){
                        $xml_content = $record->content;
                        $cfdi = Cfdi::newFromString($xml_content);
                        $comp = $cfdi->getQuickReader();
                        $emisor = $comp->Emisor;
                        $receptor = $comp->Receptor;
                        $conceptos = $comp->Conceptos;
                        $partidas = [];
                        foreach($conceptos() as $concepto){
                            $partidas []= [
                                'Clave'=>$concepto['ClaveProdServ'],
                                'Descripcion'=>$concepto['Descripcion'],
                                'Cantidad'=>$concepto['Cantidad'],
                                'Unidad'=>$concepto['ClaveUnidad'],
                                'Precio'=>$concepto['ValorUnitario'],
                                'Subtotal'=>$concepto['Importe'],
                            ];
                        }
                        $ret = $comp->impuestos->retenciones;
                        $ret_iva = 0;
                        $ret_isr = 0;
                        foreach ($ret() as $ret) {
                            if($ret['Impuesto'] == '002') $ret_iva = $ret['Importe'];
                            if($ret['Impuesto'] == '001') $ret_isr = $ret['Importe'];
                        }

                        return $form
                            ->disabled(true)
                            ->schema([
                                TextInput::make('Serie y Folio')
                                    ->default(function () use ($comp){
                                        return $comp['serie'].$comp['folio'];
                                    }),
                                TextInput::make('Fecha')
                                    ->default(function () use ($comp){
                                        return $comp['fecha'];
                                    }),
                                TextInput::make('Moneda')
                                    ->default(function () use ($comp){
                                        return $comp['moneda'];
                                    }),
                                TextInput::make('TC')->label('T.C.')
                                    ->default(function () use ($comp){
                                        return $comp['TipoCambio'];
                                    })->currencyMask(precision: 4)->prefix('$'),
                                TextInput::make('Emisor')
                                    ->default(function () use ($emisor){
                                        return $emisor['rfc'].'-'.$emisor['nombre'];
                                    })->columnSpan(2),
                                TextInput::make('Receptor')
                                    ->default(function () use ($receptor){
                                        return $receptor['rfc'].'-'.$receptor['nombre'];
                                    })->columnSpan(2),
                                TableRepeater::make('partidas')
                                    ->streamlined()->addable(false)->deletable(false)->reorderable(false)
                                    ->headers([
                                        Header::make('Cantidad'),
                                        Header::make('Clave'),
                                        Header::make('Descripcion')->width('350px'),
                                        Header::make('Unidad'),
                                        Header::make('Precio'),
                                        Header::make('Subtotal'),
                                    ])
                                    ->schema([
                                        TextInput::make('Cantidad'),
                                        TextInput::make('Clave'),
                                        TextInput::make('Descripcion'),
                                        TextInput::make('Unidad'),
                                        TextInput::make('Precio')->currencyMask()->prefix('$'),
                                        TextInput::make('Subtotal')->currencyMask()->prefix('$'),
                                    ])->default($partidas)
                                    ->columnSpanFull(),
                                Group::make([
                                    TextInput::make('Subtotal')
                                        ->default(function () use ($comp){
                                            return $comp['subtotal'];
                                        })->inlineLabel()->currencyMask()->prefix('$'),
                                    TextInput::make('IVA')->label('I.V.A')
                                        ->default(function () use ($comp){
                                            return $comp->impuestos['totalImpuestosTrasladados'];
                                        })->inlineLabel()->currencyMask()->prefix('$'),
                                    TextInput::make('RETIVA')->label('RET I.V.A')
                                        ->default(function () use ($ret_iva){
                                            return $ret_iva ?? 0;
                                        })->inlineLabel()->currencyMask()->prefix('$'),
                                    TextInput::make('RETISR')->label('RET I.S.R')
                                        ->default(function () use ($ret_isr){
                                            return $ret_isr ?? 0;
                                        })->inlineLabel()->currencyMask()->prefix('$'),
                                    TextInput::make('Total')
                                        ->default(function () use ($comp){
                                            return $comp['total'];
                                        })->inlineLabel()->currencyMask()->prefix('$'),
                                ])
                            ])->columns(4);
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
                ])
            ])->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
                BulkAction::make('multi_Contabilizar')
                ->label('Contabilizar')
                ->tooltip('Contabilizar')
                ->icon('fas-scale-balanced')
                ->modalWidth(MaxWidth::ExtraSmall)
                ->form([
                    TextInput::make('dias_cred')
                        ->label('Dias de Crédito')
                        ->numeric()->default(60),
                    Select::make('rubrogas')
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
                    Select::make('detallegas')
                        ->label('Rubro del Gasto')
                        ->required()
                        ->options(function(Get $get) {
                            return
                            CatCuentas::where('acumula',$get('rubrogas'))->pluck('nombre','codigo');
                        }),
                    Select::make('forma')
                        ->label('Forma de Pago')
                        ->default('CXP')
                        ->options([
                            'CXP'=>'Cuenta por Pagar',
                            'TER'=>'Pagado por Tercero'
                        ])
                        ->live(onBlur: true)
                        ->required(),
                    Select::make('Tercero')
                        ->searchable()
                        ->visible(function(Get $get){
                            if($get('forma')== 'TER') return true;
                            else return false;
                        })
                        ->required(function(Get $get){
                            if($get('forma')== 'TER') return true;
                            else return false;
                        })
                        ->options(Terceros::select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
                        ->createOptionForm(function($form){
                            return $form
                                ->schema([
                                    TextInput::make('rfc')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('nombre')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(3),
                                    TextInput::make('tipo')
                                        ->label('Tipo de Tercero')
                                        ->default('Acreedor')
                                        ->readOnly(),
                                    TextInput::make('cuenta')
                                        ->required()
                                        ->maxLength(255)
                                        ->readOnly()
                                        ->default(function(){
                                            $nuecta = 20501000;
                                            $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->get() ?? 0);
                                            if($rg > 0)
                                                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->max('codigo')) + 1000;
                                            return $nuecta;
                                        }),
                                    TextInput::make('telefono')
                                        ->tel()
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('correo')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('contacto')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('regimen')
                                        ->searchable()
                                        ->label('Regimen Fiscal')
                                        ->columnSpan(2)
                                        ->options(Regimenes::all()->pluck('mostrar','clave')),
                                    Hidden::make('tax_id')
                                        ->default(Filament::getTenant()->taxid),
                                    Hidden::make('team_id')
                                        ->default(Filament::getTenant()->id),
                                    TextInput::make('codigopos')
                                        ->label('Codigo Postal')
                                        ->required()
                                        ->maxLength(255),
                                ])->columns(4);
                        })
                        ->createOptionUsing(function(array $data){
                            $recor = DB::table('terceros')->insertGetId($data);
                            DB::table('cat_cuentas')->insert([
                                'nombre' =>  $data['nombre'],
                                'team_id' => Filament::getTenant()->id,
                                'codigo'=>$data['cuenta'],
                                'acumula'=>'20500000',
                                'tipo'=>'D',
                                'naturaleza'=>'A',
                            ]);
                            $rec = Terceros::where('id',$recor)->get()[0];
                            return $rec->nombre.'|'.$rec->cuenta;
                        })
                ])
                ->action(function(Collection $records,array $data,$livewire){
                    $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PG')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    foreach($records as $record){
                        self::contabiliza_r($record,$data,$livewire,$nopoliza);
                        $nopoliza++;
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

    public static function contabiliza_r($record,$data,$livewire,$nopoliza)
    {
        $tipoxml = $record['xml_type'];
        $tipocom = $record['TipoDeComprobante'];
        $rfc_rec = $record['Receptor_Rfc'];
        $rfc_emi = $record['Emisor_Rfc'];
        $nom_rec = $record['Receptor_Nombre'];
        $nom_emi = $record['Emisor_Nombre'];
        $descuento = $record['Descuento'];
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
        $xml_content = $record->content;
        $cfdi = Cfdi::newFromString($xml_content);
        $comp = $cfdi->getQuickReader();
        $ret = $comp->impuestos->retenciones;
        $ret_iva = 0;
        $ret_isr = 0;
        foreach ($ret() as $ret) {
            if($ret['Impuesto'] == '002') $ret_iva = $ret['Importe'];
            if($ret['Impuesto'] == '001') $ret_isr = $ret['Importe'];
        }
        list($cffecha,$cfhora) = explode('T',$cffecha1);
        $forma = $data['forma'] ?? 'CXP';
        $ctagas = $data['detallegas'];
        if($tipoc == null||$tipoc == ''||$tipoc ==0) $tipoc = 1;
        if($tipoxml == 'Recibidos'&&$tipocom == 'I')
        {
            $existe = CatCuentas::where('nombre',$nom_emi)->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first();
            if($existe)
            {
                $ctaclie = $existe->codigo;
            }
            else
            {
                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20101000')->max('codigo')) + 1;
                CatCuentas::firstOrCreate([
                    'nombre' =>  $nom_emi,
                    'team_id' => Filament::getTenant()->id,
                    'codigo'=>$nuecta,
                    'acumula'=>'20101000',
                    'tipo'=>'D',
                    'naturaleza'=>'A',
                ]);
                Terceros::create([
                    'rfc'=>$rfc_emi,
                    'nombre'=>$nom_emi,
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
            Almacencfdis::where('id',$record['id'])->update([
                'metodo'=>$forma
            ]);
            $ntotal = floatval($total);
            $nsubtotal = floatval($subtotal) - floatval($descuento);
            //dd($ntotal,$nsubtotal,$total,$subtotal);
            if($data['forma'] == 'CXP') {
                $poliza = CatPolizas::create([
                    'tipo' => 'PG',
                    'folio' => $nopoliza,
                    'fecha' => $cffecha,
                    'concepto' => $nom_emi,
                    'cargos' => $ntotal * $tipoc,
                    'abonos' => $ntotal * $tipoc,
                    'periodo' => $cfperiodo,
                    'ejercicio' => $cfejercicio,
                    'referencia' => $serie . $folio,
                    'uuid' => $uuid,
                    'tiposat' => 'Dr',
                    'team_id' => Filament::getTenant()->id,
                    'idcfdi' => $record->id
                ]);
                $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ctaclie,
                    'cuenta' => $nom_emi,
                    'concepto' => $nom_emi,
                    'cargo' => 0,
                    'abono' => $ntotal * $tipoc,
                    'factura' => $serie . $folio,
                    'nopartida' => 1,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ctagas,
                    'cuenta' => 'Ventas',
                    'concepto' => $nom_emi,
                    'cargo' => $nsubtotal * $tipoc,
                    'abono' => 0,
                    'factura' => $serie . $folio,
                    'nopartida' => 2,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => '11901000',
                    'cuenta' => 'IVA trasladado no cobrado',
                    'concepto' => $nom_emi,
                    'cargo' => $iva * $tipoc,
                    'abono' => 0,
                    'factura' => $serie . $folio,
                    'nopartida' => 3,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $rets = 3;
                if($ret_iva > 0){
                    $rets++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '21610000',
                        'cuenta' => 'Impuestos retenidos de IVA',
                        'concepto' => $nom_emi,
                        'cargo' => 0,
                        'abono' => $ret_iva,
                        'factura' => $serie . $folio,
                        'nopartida' => $rets,
                        'uuid' => $uuid,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
                if($ret_isr > 0){
                    $rets++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '21604000',
                        'cuenta' => 'Impuestos ret de ISR x servicios prof',
                        'concepto' => $nom_emi,
                        'cargo' => 0,
                        'abono' => $ret_isr,
                        'factura' => $serie . $folio,
                        'nopartida' => $rets,
                        'uuid' => $uuid,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }

                try {
                    $prov = Proveedores::where('rfc', $rfc_emi)
                        ->where('team_id', Filament::getTenant()->id)
                        ->first();
                    $proveedorId = $prov?->id ?? null;
                    $tc = ($tipoc == null || $tipoc == '' || $tipoc == 0) ? 1 : floatval($tipoc);
                    $diasCredito = intval($data['dias_cred'] ?? 0);
                    $fechaDoc = Carbon::parse($cffecha);
                    $venc = (clone $fechaDoc)->addDays($diasCredito);
                    if(!$proveedorId){
                        $cve = Proveedores::where('team_id',Filament::getTenant()->id)
                                ->max('id') + 1;
                        $proveedorId = Proveedores::Create([
                            'clave'=>$cve,
                            'rfc'=>$rfc_emi,
                            'nombre' => $nom_emi,
                            'dias_credito' => $diasCredito,
                            'team_id'=>Filament::getTenant()->id,
                            'cuenta_contable'=>$ctaclie
                        ])->id;
                    }
                    if ($proveedorId) {
                        $existeCxp = CuentasPagar::where('team_id', Filament::getTenant()->id)
                            ->where('refer', $record->id)
                            ->first();
                        if (!$existeCxp) {
                            CuentasPagar::create([
                                'proveedor'   => $proveedorId,
                                'concepto'    => 1,
                                'descripcion' => 'CFDI Proveedor',
                                'documento'   => ($serie . $folio) ?: $uuid,
                                'fecha'       => $fechaDoc->toDateString(),
                                'vencimiento' => $venc->toDateString(),
                                'importe'     => $ntotal * $tc,
                                'saldo'       => $ntotal * $tc,
                                'team_id'     => Filament::getTenant()->id,
                                'refer'       => $record->id,
                            ]);
                            // Ajustar saldo del proveedor al crear la CxP desde CFDI
                            Proveedores::where('id', $proveedorId)->increment('saldo', ($ntotal * $tc));
                        }
                    }
                } catch (\Throwable $e) {
                    dd($e->getMessage());
                }

            }
            else
            {
                $poliza = CatPolizas::create([
                    'tipo' => 'CG',
                    'folio' => $nopoliza,
                    'fecha' => $cffecha,
                    'concepto' => $nom_emi,
                    'cargos' => $ntotal * $tipoc,
                    'abonos' => $ntotal * $tipoc,
                    'periodo' => $cfperiodo,
                    'ejercicio' => $cfejercicio,
                    'referencia' => $serie . $folio,
                    'uuid' => $uuid,
                    'tiposat' => 'Dr',
                    'team_id' => Filament::getTenant()->id,
                    'idcfdi' => $record->id
                ]);
                $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ctagas,
                    'cuenta' => $nom_emi,
                    'concepto' => $nom_emi,
                    'cargo' => $nsubtotal * $tipoc,
                    'abono' => 0,
                    'factura' => $serie . $folio,
                    'nopartida' => 1,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => '11801000',
                    'cuenta' => 'IVA acreditable pagado',
                    'concepto' => $nom_emi,
                    'cargo' => $iva * $tipoc,
                    'abono' => 0,
                    'factura' => $serie . $folio,
                    'nopartida' => 2,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ctaclie,
                    'cuenta' => $nom_emi,
                    'concepto' => $nom_emi,
                    'cargo' => $ntotal * $tipoc,
                    'abono' => 0,
                    'factura' => $serie . $folio,
                    'nopartida' => 3,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ctaclie,
                    'cuenta' => $nom_emi,
                    'concepto' => $nom_emi,
                    'cargo' => 0,
                    'abono' => $ntotal * $tipoc,
                    'factura' => $serie . $folio,
                    'nopartida' => 4,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $terc = explode('|',$data['Tercero']);
                $ter_nombre = $terc[0];
                $ter_cuenta = $terc[1];
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $ter_cuenta,
                    'cuenta' => $ter_nombre,
                    'concepto' => $nom_emi,
                    'cargo' => 0,
                    'abono' => $ntotal * $tipoc,
                    'factura' => $serie . $folio,
                    'nopartida' => 5,
                    'uuid' => $uuid,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
                $rets = 5;
                if($ret_iva > 0){
                    $rets++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '21610000',
                        'cuenta' => 'Impuestos retenidos de IVA',
                        'concepto' => $nom_emi,
                        'cargo' => 0,
                        'abono' => $ret_iva,
                        'factura' => $serie . $folio,
                        'nopartida' => $rets,
                        'uuid' => $uuid,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
                if($ret_isr > 0){
                    $rets++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '21604000',
                        'cuenta' => 'Impuestos ret de ISR x servicios prof',
                        'concepto' => $nom_emi,
                        'cargo' => 0,
                        'abono' => $ret_isr,
                        'factura' => $serie . $folio,
                        'nopartida' => $rets,
                        'uuid' => $uuid,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
            }
            DB::table('almacencfdis')->where('id',$record->id)->update([
                'used'=> 'SI',
                'metodo'=>$forma
            ]);
            if($record['Moneda'] == 'USD'||$record['Moneda'] == 'usd'||$record['Moneda']=='Usd'){
                DB::table('usd_movs')->insert([
                    'xml_id'=>$record->id,
                    'poliza'=>$polno,
                    'subtotalusd'=>$nsubtotal,
                    'ivausd'=>$iva,
                    'totalusd'=>$ntotal,
                    'subtotalmxn'=>$nsubtotal * $tipoc,
                    'ivamxn'=>$iva * $tipoc,
                    'totalmxn'=>$ntotal * $tipoc,
                    'tcambio'=>$tipoc,
                    'uuid'=>$uuid,
                    'referencia'=>$serie.$folio
                ]);
            }
            DB::table('ingresos_egresos')->insert([
                'xml_id'=>$record->id,
                'poliza'=>$polno,
                'subtotalusd'=>$nsubtotal,
                'ivausd'=>$iva,
                'totalusd'=>$ntotal,
                'subtotalmxn'=>$nsubtotal * $tipoc,
                'ivamxn'=>$iva * $tipoc,
                'totalmxn'=>$total * $tipoc,
                'tcambio'=>$tipoc,
                'uuid'=>$uuid,
                'referencia'=>$serie.$folio,
                'pendientemxn'=>$ntotal * $tipoc,
                'pendienteusd'=>$ntotal,
                'pagadousd'=>0,
                'pagadomxn'=>0,
                'tipo'=>0,
                'periodo'=>$cfperiodo,
                'ejercicio'=>$cfejercicio,
                'team_id'=>Filament::getTenant()->id
            ]);

            Notification::make()
                ->title('Contabilizar')
                ->body('Poliza '.$nopoliza.' Generada Correctamente')
                ->success()
                ->send();
                $livewire->resetTable();
        }
    }
}
