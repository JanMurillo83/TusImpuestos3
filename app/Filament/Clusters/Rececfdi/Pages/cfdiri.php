<?php

namespace App\Filament\Clusters\Rececfdi\Pages;

use App\Filament\Clusters\Rececfdi;
use App\Models\Activosfijos;
use App\Models\Admincuentaspagar;
use App\Models\Almacencfdis;
use App\Models\AuxCFDI;
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
use Filament\Forms\Form;
use Filament\Forms\Set;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use stdClass;

class cfdiri extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $cluster = Rececfdi::class;
    protected static ?string $title = 'Facturas Recibidos';
    protected static string $view = 'filament.clusters.rececfdi.pages.cfdiri';
    protected static ?string $headerActionsposition = 'bottom';
    public ?Date $Fecha_Inicial = null;
    public ?Date $Fecha_Final = null;

    public function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->query(Almacencfdis::query())
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('team_id',Filament::getTenant()->id)
                    ->where('xml_type','Recibidos')
                    ->where('TipoDeComprobante','I')
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
                Action::make('ContabilizarR')
                    ->iconButton()
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
                            ->numeric()->default(30)->visible(false),
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
                        $uuid_v = $record->UUID;
                        $pols = CatPolizas::where('team_id',Filament::getTenant()->id)
                            ->where('tipo','PG')->pluck('id');
                        $aux = \count(Auxiliares::where('team_id',Filament::getTenant()->id)
                            ->whereIn('cat_polizas_id',$pols)->where('uuid',$uuid_v)->get());
                        if($aux > 0){
                            Almacencfdis::where('id',$record->id)->update([
                                'used'=>'SI'
                            ]);
                            Notification::make()->title('CFDI Contabilizado Previamente')->warning()->send();
                            return;
                        }
                        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PG')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                        $cta_con = '20101001';
                        $cta_nombres = 'Proveedor Global';

                        if(!Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->exists())
                        {

                            if(CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->exists())
                            {
                                $cta_con = CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->codigo;
                                $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                            }
                            else
                            {
                                $nuecta = intval(DB::table('cat_cuentas')
                                        ->where('team_id',Filament::getTenant()->id)
                                        ->where('acumula','20101000')->max('codigo')) + 1;
                                $n_cta = CatCuentas::create([
                                    'nombre' =>  $record['Emisor_Nombre'],
                                    'team_id' => Filament::getTenant()->id,
                                    'codigo'=>$nuecta,
                                    'acumula'=>'20101000',
                                    'tipo'=>'D',
                                    'naturaleza'=>'A',
                                ]);
                                $cta_con = $n_cta->codigo;
                                $cta_nombres = $n_cta->nombre;
                            }
                            $nuevocli = Count(Proveedores::where('team_id',Filament::getTenant()->id)->get()) + 1;
                            Proveedores::create([
                                'clave' => $nuevocli,
                                'rfc'=>$record['Emisor_Rfc'],
                                'nombre'=>$record['Emisor_Nombre'],
                                'cuenta_contable'=>$cta_con,
                                'team_id' => Filament::getTenant()->id,
                            ]);
                        }
                        else
                        {
                            $cuen = Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->first()->cuenta_contable;
                            if($cuen != ''&&$cuen != null)
                            {
                                $cta_con = $cuen;
                                $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                            }
                            else
                            {
                                if(CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->exists()){
                                    $cta_con = CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->codigo;
                                    $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                                }
                                else
                                {
                                    $nuecta = intval(DB::table('cat_cuentas')
                                            ->where('team_id',Filament::getTenant()->id)
                                            ->where('acumula','20101000')->max('codigo')) + 1;
                                    $n_cta = CatCuentas::create([
                                        'nombre' =>  $record['Emisor_Nombre'],
                                        'team_id' => Filament::getTenant()->id,
                                        'codigo'=>$nuecta,
                                        'acumula'=>'20101000',
                                        'tipo'=>'D',
                                        'naturaleza'=>'A',
                                    ]);
                                    $cta_con = $n_cta->codigo;
                                    $cta_nombres =$n_cta->nombre;
                                }
                            }
                            Proveedores::where('team_id',Filament::getTenant()->id)
                                ->where('rfc',$record['Emisor_Rfc'])
                                ->update(['cuenta_contable'=>$cta_con]);
                        }
                        self::contabiliza_r($record,$data,$livewire,$nopoliza);
                    }),
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
                                TextInput::make('Método de Pago')
                                    ->default(function () use ($comp){
                                        return $comp['MetodoPago'];
                                    }),
                                TextInput::make('Forma de Pago')
                                    ->default(function () use ($comp){
                                        return $comp['FormaPago'];
                                    }),
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
                            $nombre = $record->Emisor_Rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$record->Emisor_Rfc.'.xml';
                            $archivo = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                            if(File::exists($archivo)) unlink($archivo);
                            $xml = $record->content;
                            $xml = Cleaner::staticClean($xml);
                            File::put($archivo,$xml);
                            $ruta = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                            return response()->download($ruta);
                        }),
                    Action::make('Activo Fijo')
                        ->icon('fas-truck-plane')
                        ->modalSubmitActionLabel('Grabar')
                        ->form(function(Form $form,$record){
                            $xmlContents = $record->content;
                            $cfdiData = \CfdiUtils\Cfdi::newFromString($xmlContents);
                            $comprobante = $cfdiData->getQuickReader();
                            $emisor = $comprobante->emisor;
                            $receptor = $comprobante->receptor;
                            $concepto = $comprobante->Conceptos->Concepto;
                            $impuestos = $comprobante->impuestos;
                            $tfd = $comprobante->complemento->TimbreFiscalDigital;
                            $subtotal = floatval($comprobante['subtotal']);
                            $iva = floatval($impuestos->Traslados->Traslado['Importe']);
                            $retiva = floatval($impuestos->Retenciones->Retencion['Importe'] ?? 0);
                            $total = floatval($comprobante['total']);
                            $prov_id = self::ValProveedor($record);
                            $proveedor = Proveedores::where('id',$prov_id)->first();
                            return $form
                                ->schema([
                                    TextInput::make('clave')
                                        ->maxLength(255)->default($concepto['NoIdentificacion'] ?? '00000'),
                                    Select::make('tipoact')
                                        ->label('Tipo de Activo')
                                        ->live()
                                        ->options([
                                            '15100000|17101000'=>'Terrenos',
                                            '15200000|17102000'=>'Edificios',
                                            '15300000|17103000'=>'Maquinaria y equipo',
                                            '15400000|17104000'=>'Automoviles, autobuses, camiones de carga',
                                            '15500000|17105000'=>'Mobiliario y equipo de oficina',
                                            '15600000|17106000'=>'Equipo de computo',
                                            '15700000|17107000'=>'Equipo de comunicacion',
                                            '15800000|17108000'=>'Activos biologicos, vegetales y semovientes',
                                            '15900000|17109000'=>'Obras en proceso de activos fijos',
                                            '16000000|17110000'=>'Otros activos fijos',
                                            '16100000|17111000'=>'Ferrocariles',
                                            '16200000|17112000'=>'Embarcaciones',
                                            '16300000|17113000'=>'Aviones',
                                            '16400000|17114000'=>'Troqueles, moldes, matrices y herramental',
                                            '16500000|17115000'=>'Equipo de comunicaciones telefonicas',
                                            '16600000|17116000'=>'Equipo de comunicacion satelital',
                                            '16700000|17117000'=>'Eq de adaptaciones para personas con capac dif',
                                            '16800000|17118000'=>'Maq y eq de generacion de energia de ftes renov',
                                            '16900000|17119000'=>'Otra maquinaria y equipo',
                                            '17000000|17120000'=>'Adaptaciones y mejoras'
                                        ])
                                        ->afterStateUpdated(function(Get $get,Set $set){
                                            $nucta = $get('tipoact');
                                            $nucta = explode('|',$nucta);
                                            $set('cuentadep',$nucta[1]);
                                            $nuecta = $nucta[0];
                                            $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula',$nuecta)->get() ?? 0);
                                            if($rg > 0)
                                                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula',$nuecta)->max('codigo')) + 1000;
                                            $set('cuentaact',$nuecta);
                                        }),
                                    TextInput::make('descripcion')
                                        ->maxLength(255)
                                        ->columnSpanFull()->default($concepto['Descripcion']),
                                    TextInput::make('marca')
                                        ->maxLength(255),
                                    TextInput::make('modelo')
                                        ->maxLength(255),
                                    TextInput::make('serie')
                                        ->maxLength(255),
                                    TextInput::make('importe')
                                        ->label('Importe Original')
                                        ->required()
                                        ->numeric()
                                        ->prefix('$')
                                        ->default($subtotal),
                                    TextInput::make('depre')
                                        ->label('Tasa de Depreciacion')
                                        ->required()
                                        ->numeric()
                                        ->postfix('%')
                                        ->default(0),
                                    TextInput::make('acumulado')
                                        ->label('Depreciacion acumulada')
                                        ->required()
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)->readOnly(),
                                    Select::make('proveedor')
                                        ->searchable()
                                        ->options(Proveedores::where('team_id',Filament::getTenant()->id)->get()->pluck('nombre','id'))
                                        ->default($proveedor->id),
                                    TextInput::make('cuentadep')
                                        ->label('Cuenta Depreciacion')
                                        ->maxLength(255)
                                        ->readOnly(),
                                    TextInput::make('cuentaact')
                                        ->label('Cuenta Activo Fijo')
                                        ->maxLength(255)
                                        ->readOnly(),
                                    Hidden::make('tax_id')
                                        ->default(Filament::getTenant()->tax_id),
                                    Hidden::make('team_id')
                                        ->default(Filament::getTenant()->id),
                                    Hidden::make('impuesto')->default($iva),
                                ])->columns(3);
                        })
                    ->action(function($record,$data){
                        $prov = Proveedores::where('id',$data['proveedor'])->first();
                        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                        $dats = Carbon::now();
                        $fecha = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo.'-'.$dats->day;
                        $factura = $record->Serie.$record->Folio;
                        $uuid = $record->UUID;
                        $importe = floatval($record->Total);
                        $impuesto = floatval($data['impuesto']);
                        $tipoc = floatval($record->TipoCambio);
                        $subtotal = floatval($record->SubTotal);
                        DB::table('cat_cuentas')->insert([
                            'nombre' =>  $data['descripcion'],
                            'team_id' => Filament::getTenant()->id,
                            'codigo'=>$data['cuentaact'],
                            'acumula'=>'15400000',
                            'tipo'=>'D',
                            'naturaleza'=>'D',
                        ]);
                        Activosfijos::create([
                            'clave'=>$data['clave'],
                            'descripcion'=>$data['descripcion'],
                            'marca'=>$data['marca'],
                            'modelo'=>$data['modelo'],
                            'serie'=>$data['serie'],
                            'proveedor'=>$prov->id,
                            'importe'=>$subtotal,
                            'depre'=>$data['depre'],
                            'acumulado'=>$data['acumulado'],
                            'cuentadep'=>$data['cuentadep'],
                            'cuentaact'=>$data['cuentaact'],
                            'team_id'=>Filament::getTenant()->id,
                        ]);

                        $poliza = CatPolizas::create([
                            'tipo'=>'Dr',
                            'folio'=>$nopoliza,
                            'fecha'=>$fecha,
                            'concepto'=>'Registro de Activo Fijo',
                            'cargos'=>$importe,
                            'abonos'=>$importe,
                            'periodo'=>Filament::getTenant()->periodo,
                            'ejercicio'=>Filament::getTenant()->ejercicio,
                            'referencia'=>$factura,
                            'uuid'=>$uuid,
                            'tiposat'=>'Dr',
                            'team_id'=>Filament::getTenant()->id,
                            'idcfdi'=>$record->id,
                        ]);
                        $polno = $poliza['id'];
                        $ing_id = DB::table('ingresos_egresos')->insertGetId([
                            'xml_id'=>$record->id,
                            'poliza'=>$polno,
                            'subtotalusd'=>$subtotal,
                            'ivausd'=>$impuesto,
                            'totalusd'=>$importe,
                            'subtotalmxn'=>$subtotal * $tipoc,
                            'ivamxn'=>$impuesto * $tipoc,
                            'totalmxn'=>$importe * $tipoc,
                            'tcambio'=>$tipoc,
                            'uuid'=>$uuid,
                            'referencia'=>$factura,
                            'pendientemxn'=>$importe * $tipoc,
                            'pendienteusd'=>$importe,
                            'pagadousd'=>0,
                            'pagadomxn'=>0,
                            'tipo'=>0,
                            'periodo'=>Filament::getTenant()->periodo,
                            'ejercicio'=>Filament::getTenant()->ejercicio,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$data['cuentaact'],
                            'cuenta'=>$data['descripcion'],
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>$subtotal,
                            'abono'=>0,
                            'factura'=>$factura,
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
                            'codigo'=>'11901000',
                            'cuenta'=>'IVA pendiente de pago',
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>$impuesto,
                            'abono'=> 0,
                            'factura'=>$factura,
                            'uuid'=>$uuid,
                            'nopartida'=>2,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$prov->cuenta_contable,
                            'cuenta'=>$prov->nombre,
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>0,
                            'abono'=>$importe,
                            'factura'=>$factura,
                            'uuid'=>$uuid,
                            'nopartida'=>3,
                            'team_id'=>Filament::getTenant()->id,
                            'igeg_id'=>$ing_id,
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);

                        DB::table('almacencfdis')->where('id',$record->id)->update([
                            'used'=> 'SI',
                        ]);
                        Notification::make()->title('Registro Grabado, Poliza Dr'.$nopoliza.' Grabada')->success()->send();
                    })
                ])
            ])->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                BulkAction::make('multi_Contabilizar')
                ->label('Contabilizar')
                ->tooltip('Contabilizar')
                ->icon('fas-scale-balanced')
                ->modalWidth(MaxWidth::ExtraSmall)
                ->form([
                    TextInput::make('dias_cred')
                        ->label('Dias de Crédito')
                        ->numeric()->default(30)->visible(false),
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
                        $cta_con = '20101001';
                        $cta_nombres = 'Proveedor Global';
                        if(!Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->exists())
                        {

                            if(CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->exists())
                            {
                                $cta_con = CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->codigo;
                                $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                            }
                            else
                            {
                                $nuecta = intval(DB::table('cat_cuentas')
                                        ->where('team_id',Filament::getTenant()->id)
                                        ->where('acumula','20101000')->max('codigo')) + 1;
                                $n_cta = CatCuentas::create([
                                    'nombre' =>  $record['Emisor_Nombre'],
                                    'team_id' => Filament::getTenant()->id,
                                    'codigo'=>$nuecta,
                                    'acumula'=>'20101000',
                                    'tipo'=>'D',
                                    'naturaleza'=>'A',
                                ]);
                                $cta_con = $n_cta->codigo;
                                $cta_nombres = $n_cta->nombre;
                            }
                            $nuevocli = Count(Proveedores::where('team_id',Filament::getTenant()->id)->get()) + 1;
                            Proveedores::create([
                                'clave' => $nuevocli,
                                'rfc'=>$record['Emisor_Rfc'],
                                'nombre'=>$record['Emisor_Nombre'],
                                'cuenta_contable'=>$cta_con,
                                'team_id' => Filament::getTenant()->id,
                            ]);
                        }
                        else
                        {
                            $cuen = Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->first()->cuenta_contable;
                            if($cuen != ''&&$cuen != null)
                            {
                                $cta_con = $cuen;
                                $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                            }
                            else
                            {
                                if(CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->exists()){
                                    $cta_con = CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->codigo;
                                    $cta_nombres =CatCuentas::where('nombre',$record['Emisor_Nombre'])->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first()->nombre;
                                }
                                else
                                {
                                    $nuecta = intval(DB::table('cat_cuentas')
                                            ->where('team_id',Filament::getTenant()->id)
                                            ->where('acumula','20101000')->max('codigo')) + 1;
                                    $n_cta = CatCuentas::create([
                                        'nombre' =>  $record['Emisor_Nombre'],
                                        'team_id' => Filament::getTenant()->id,
                                        'codigo'=>$nuecta,
                                        'acumula'=>'20101000',
                                        'tipo'=>'D',
                                        'naturaleza'=>'A',
                                    ]);
                                    $cta_con = $n_cta->codigo;
                                    $cta_nombres =$n_cta->nombre;
                                }
                            }
                            Proveedores::where('team_id',Filament::getTenant()->id)
                                ->where('rfc',$record['Emisor_Rfc'])
                                ->update(['cuenta_contable'=>$cta_con]);
                        }
                        $uuid_v = $record->UUID;
                        $pols = CatPolizas::where('team_id',Filament::getTenant()->id)
                            ->where('tipo','PG')->pluck('id');
                        $aux = \count(Auxiliares::where('team_id',Filament::getTenant()->id)
                            ->whereIn('cat_polizas_id',$pols)->where('uuid',$uuid_v)->get());
                        if($aux > 0){
                            Almacencfdis::where('id',$record->id)->update([
                                'used'=>'SI'
                            ]);
                        }else {
                            self::contabiliza_r($record, $data, $livewire, $nopoliza);
                        }
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
        $rcambio = $record['TipoCambio'] ?? 0;
        if($rcambio > 0)
            $tipoc = $record['TipoCambio'];
        else
            $tipoc = 1;
        $metodo = $record['MetodoPago'];
        $serie = $record['Serie'];
        $folio = $record['Folio'];
        $uuid = $record['UUID'];
        $dat_aux = AuxCFDI::where('uuid',$uuid)->first();
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
            if(!DB::table('proveedores')->where('team_id',$record->team_id)->where('rfc',$rfc_emi)->exists())
            {
                $clave = count(DB::table('proveedores')->where('team_id',$record->team_id)->get()) + 1;
                \App\Models\Proveedores::create([
                    'clave'=>$clave,
                    'rfc'=>$rfc_emi,
                    'nombre'=>$nom_emi,
                    'team_id'=>$record->team_id,
                    'dias_credito'=>30
                ]);
            }
            $existe = CatCuentas::where('rfc_asociado',$rfc_emi)->where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->first();
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
                    'rfc_asociado'=>$rfc_emi
                ]);
                Proveedores::where('rfc',$rfc_emi)
                ->where('team_id',Filament::getTenant()->id)
                ->update(['cuenta_contable'=>$nuecta]);

                $ctaclie = $nuecta;
            }
            Almacencfdis::where('id',$record['id'])->update([
                'metodo'=>$forma
            ]);
            $ntotal = floatval($total);
            $nsubtotal = floatval($subtotal) - floatval($descuento);
            //dd($ntotal,$nsubtotal,$total,$subtotal);
            if($data['forma'] == 'CXP') {
                $prov_ee = Proveedores::where('rfc',$rfc_emi)->where('team_id',Filament::getTenant()->id)->first();
                $dias_cred = intval($prov_ee->dias_credito);
                $saldo_ant = floatval($prov_ee->saldo);
                $saldo_nue = $saldo_ant + ($ntotal*$tipoc);
                Proveedores::where('id',$prov_ee->id)->increment('saldo',$ntotal*$tipoc);

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
                $cfecha_ven = Carbon::create($cffecha)->addDays($dias_cred);
                Admincuentaspagar::create([
                    'clave'=>$prov_ee->id,
                    'referencia'=>$record['id'],
                    'uuid'=>$uuid,
                    'fecha'=>$cffecha,
                    'vencimiento'=>$cfecha_ven,
                    'moneda'=>$record['Moneda'] ?? 'MXN',
                    'tcambio'=>$tipoc,
                    'importe'=>$ntotal*$tipoc,
                    'importeusd'=>$ntotal,
                    'saldo'=>$ntotal*$tipoc,
                    'saldousd'=>$ntotal,
                    'periodo'=>Filament::getTenant()->periodo,
                    'ejercicio'=>Filament::getTenant()->ejercicio,
                    'periodo_ven'=>Carbon::create($cfecha_ven)->format('m'),
                    'ejercicio_ven'=>Carbon::create($cfecha_ven)->format('Y'),
                    'poliza'=>$polno,
                    'team_id'=>Filament::getTenant()->id,
                ]);
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
                if($dat_aux->iva > 0) {
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
                }
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
                if($dat_aux->iva > 0) {
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
                }
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

    public static function ValProveedor($record) : int
    {
        $prov_id = 0;
        if(Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->exists())
        {
            $prov = Proveedores::where('team_id',Filament::getTenant()->id)->where('rfc',$record['Emisor_Rfc'])->first();
            $prov_id = $prov->id;
            if($prov->cuenta_contable == ''||$prov->cuenta_contable == null)
            {
                if(CatCuentas::where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->where('nombre',$prov->nombre)->exists())
                {
                    $cta = CatCuentas::where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->where('nombre',$prov->nombre)->first();
                    Proveedores::where('id',$prov_id)->update([
                       'cuenta_contable'=> $cta->codigo
                    ]);
                }else{
                    $nuecta = intval(DB::table('cat_cuentas')
                            ->where('team_id',Filament::getTenant()->id)
                            ->where('acumula','20101000')->max('codigo')) + 1;
                    $n_cta = CatCuentas::create([
                        'nombre' =>  $record['Emisor_Nombre'],
                        'team_id' => Filament::getTenant()->id,
                        'codigo'=>$nuecta,
                        'acumula'=>'20101000',
                        'tipo'=>'D',
                        'naturaleza'=>'A',
                    ]);
                    $cta_con = $n_cta->codigo;
                    Proveedores::where('id',$prov_id)->update([
                        'cuenta_contable'=> $cta_con
                    ]);
                }
            }
        }else{
            $new_cta = '';
            if(CatCuentas::where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->where('nombre',$record['Emisor_Nombre'])->exists())
            {
                $cta = CatCuentas::where('acumula','20101000')->where('team_id',Filament::getTenant()->id)->where('nombre',$record['Emisor_Nombre'])->first();
                $new_cta = $cta->codigo;
            }else{
                $nuecta = intval(DB::table('cat_cuentas')
                        ->where('team_id',Filament::getTenant()->id)
                        ->where('acumula','20101000')->max('codigo')) + 1;
                $n_cta = CatCuentas::create([
                    'nombre' =>  $record['Emisor_Nombre'],
                    'team_id' => Filament::getTenant()->id,
                    'codigo'=>$nuecta,
                    'acumula'=>'20101000',
                    'tipo'=>'D',
                    'naturaleza'=>'A',
                ]);
                $new_cta = $n_cta->codigo;
            }
            $nuevocli = Count(Proveedores::where('team_id',Filament::getTenant()->id)->get()) + 1;
            $prov_id = Proveedores::insertGetId([
                'clave' => $nuevocli,
                'rfc'=>$record['Emisor_Rfc'],
                'nombre'=>$record['Emisor_Nombre'],
                'cuenta_contable'=>$new_cta,
                'team_id' => Filament::getTenant()->id,
            ]);
        }
        return $prov_id;
    }
}
