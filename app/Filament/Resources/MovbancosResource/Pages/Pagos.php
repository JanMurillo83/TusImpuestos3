<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use App\Http\Controllers\DescargaSAT;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\IngresosEgresos;
use App\Models\CuentasPagar;
use App\Models\Proveedores;
use App\Models\Movbancos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use Dvarilek\FilamentTableSelect\Components\Form\TableSelect;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Pagos extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = MovbancosResource::class;

    protected static string $view = 'filament.resources.movbancos-resource.pages.pagos';

    public ?array $data = [];
    public ?string $fecha = null;
    public ?string $monto = null;
    public ?string $pendiente = null;
    public ?string $concepto = null;
    public ?string $cuenta = null;
    public ?string $moneda = null;
    public ?string $factura = null;
    public ?string $numero_total = null;
    public ?string $monto_total = null;
    public ?string $monto_pago = null;
    public ?string $monto_pago_usd = null;
    public ?string $tipo_cambio = null;
    public ?array $facturas_a_pagar = null;
    public $datos_mov;
    public ?string $igeg_id = null;

    public ?array $nom_terceros = [];
    public ?int $record_id;
    public ?string $fact_nombres = '';

    public function mount($record) :void
    {
        $this->record_id = $record;
        $datos = Movbancos::where('id',$record)->first();
        Almacencfdis::where('TipoCambio','<', 1)->update(['TipoCambio' => 1.00]);
        if($datos->pendiente_apli > $datos->importe) {
            $datos->pendiente_apli = $datos->importe;
            $datos->save();
        }
        $this->datos_mov = $datos;
        $this->form->fill([
            'fecha'=> $datos->fecha,
            'monto'=> $datos->importe,
            'pendiente'=> $datos->pendiente_apli,
            'concepto'=> $datos->concepto,
            'cuenta'=> $datos->cuenta,
            'moneda'=> $datos->moneda,
            'factura'=> null,
            'monto_total' => 0,
            'monto_pago' => 0,
            'monto_pago_usd' => 0,
            'tipo_cambio'=>0,
            'numero_total' => 0,
            'facturas_a_pagar'=> [],
            'igeg_id'=>0
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Datos del Pago')
                    ->schema([
                        DatePicker::make('fecha')->readOnly(),
                        TextInput::make('monto')->numeric()->currencyMask()->prefix('$')->readOnly(),
                        TextInput::make('pendiente')->numeric()->currencyMask()->prefix('$')->readOnly(),
                        TextInput::make('moneda')->readOnly(),
                        TextInput::make('concepto')->columnSpan(2)->readOnly(),
                        Hidden::make('cuenta'),

                    ])->columnSpanFull()->columns(6),
                TableSelect::make('factura')
                    ->model(Movbancos::class)
                    ->relationship('factura','factura')
                    ->requiresSelectionConfirmation()
                    ->columnSpan(4)
                    ->disabled(function (Get $get){
                        $imp1 = $get('pendiente');
                        $imp2 = $get('monto_total');
                        if($imp1 <= $imp2) return true;
                        else return false;
                    })
                    ->optionSize(ActionSize::ExtraLarge)
                    ->selectionTable(function (Table $table) {
                        return $table
                            ->heading('Seleccionar Facturas')
                            ->columns([
                                TextColumn::make('referencia')->searchable(),
                                TextColumn::make('fecha')->searchable()
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return Carbon::parse($alm->Fecha)->format('d/m/Y');
                                    }),
                                TextColumn::make('emisor_rfc')->searchable()->label('Emisor')
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return $alm->Emisor_Rfc;
                                    }),
                                TextColumn::make('emisor_nombre')->searchable()->label('Nombre')
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return $alm->Emisor_Nombre;
                                    }),
                                TextColumn::make('moneda')->sortable()
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return $alm->Moneda;
                                    }),
                                TextColumn::make('tipocambio')->label('Tipo de Cambio')->sortable()
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return $alm->TipoCambio;
                                    })->numeric(decimalPlaces: 6, decimalSeparator: '.')->prefix('$'),
                                TextColumn::make('importe')->sortable()->searchable(query: function (Builder $query, string $search): Builder {
                                    return $query->where('total', 'like', "%{$search}%");
                                    })
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        $alm = Almacencfdis::where('id',$model->xml_id)->first();
                                        return $alm->Total;
                                    })->numeric(decimalPlaces: 2, decimalSeparator: '.'),
                                TextColumn::make('pendientemxn')->sortable()->searchable()
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        return $model->pendientemxn;
                                    })->numeric(decimalPlaces: 2, decimalSeparator: '.'),
                                TextColumn::make('pendienteusd')->sortable()->searchable()
                                    ->getStateUsing(function (IngresosEgresos $model){
                                        return $model->pendienteusd;
                                    })->numeric(decimalPlaces: 2, decimalSeparator: '.'),
                            ])
                            ->modifyQueryUsing(fn (Builder $query) => $query->where('ingresos_egresos.team_id', Filament::getTenant()->id)->where('tipo', 0)->where('pendientemxn', '>', 0)->join('almacencfdis','ingresos_egresos.xml_id','=','almacencfdis.id'));
                    })
                    ->getOptionLabelFromRecordUsing(function (IngresosEgresos $model) {
                        return "{$model->referencia}";
                    })
                    ->live(onBlur: true)
                    ->multiple()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        /*if($get('factura')) {
                            $factura = $get('factura')[0];
                            $ineg = IngresosEgresos::where('xml_id', $factura)->first();
                            $fact = Almacencfdis::where('id', $ineg->xml_id)->first();
                            $set('tercero', $fact->Emisor_Nombre);
                            $set('moneda_fac', $fact->Moneda);
                            $set('pendiente_fac', $ineg->pendientemxn);
                            $mon_pg = $ineg->pendientemxn;
                            if((floatval($ineg->pendientemxn)+floatval($get('monto_total'))) > floatval($get('pendiente'))){
                                $mon_pg = floatval($get('pendiente'));
                            }
                            $set('monto_pago', $mon_pg);
                            $set('igeg_id', $ineg->id);
                        }*/
                        if($get('factura')) {
                            $facturas = $get('factura');
                            $data_tmp = $get('facturas_a_pagar');
                            $t_cambio = $get('tipo_cambio');
                            $mon_pago = $get('moneda');
                            $pend_pag = floatval($get('pendiente'));
                            foreach ($facturas as $factura) {

                                $ineg = IngresosEgresos::where('xml_id', $factura)->first();
                                $fact = Almacencfdis::where('id', $ineg->xml_id)->first();
                                $fec = explode('T', $fact->Fecha);
                                $fecha = $fec[0];
                                $pend_f = $ineg->pendienteusd;
                                $tpen_or = $ineg->pendienteusd;
                                if($fact->Moneda != 'MXN'&&$mon_pago == 'MXN'){
                                    $pend_f = $ineg->pendienteusd * $ineg->tcambio;
                                    $n_tc = floatval($pend_pag)/floatval($fact->Total);
                                    $set('tipo_cambio', $n_tc);
                                    $pend_f = floatval($pend_pag);
                                }
                                if($fact->Moneda == 'MXN'&&$mon_pago != 'MXN'){
                                    $pend_f = $ineg->pendienteusd ?? $ineg->pendientemxn / $t_cambio;
                                    $tpen_or = $ineg->pendienteusd ?? $ineg->pendientemxn / $t_cambio;
                                }
                                if($fact->Moneda != 'MXN'&&$mon_pago != 'MXN'){
                                    $pend_f = $ineg->pendienteusd ?? $ineg->pendientemxn;
                                }
                                if($fact->Moneda == 'MXN'&&$mon_pago == 'MXN'){
                                    $pend_f = $ineg->pendienteusd ?? $ineg->pendientemxn;
                                    $tpen_or = 0;
                                }
                                if($pend_pag < $pend_f && $pend_pag > 0) $pend_f = $pend_pag;
                                if($pend_pag <= 0) $pend_f = 0;
                                $data_new = [
                                    'Referencia' => $fact->Serie . $fact->Folio,
                                    'Fecha' => $fecha,
                                    'Tercero' => $fact->Receptor_Nombre,
                                    'Moneda'=> $fact->Moneda,
                                    'Tipo Cambio' => $fact->TipoCambio,
                                    'Pendiente' => $tpen_or,
                                    'Monto a Pagar' => $pend_f,
                                    'USD a Pagar' => $tpen_or,
                                    'id_xml' => $fact->id,
                                    'id_fac' => $ineg->id,
                                    'igeg_id_id' => $ineg->id,
                                ];
                                $pend_pag -= $pend_f;
                                if(!in_array($fact->Emisor_Nombre,$this->nom_terceros))array_push($this->nom_terceros,$fact->Emisor_Nombre);
                                $this->fact_nombres.= ' '. $fact->Serie . $fact->Folio;
                                array_push($data_tmp, $data_new);
                            }
                            $sum = array_sum(array_column($data_tmp, 'Monto a Pagar'));
                            $cnt = count($data_tmp);
                            $set('numero_total', $cnt);
                            $set('monto_total', $sum);
                            $set('facturas_a_pagar', $data_tmp);
                            $set('tercero', null);
                            $set('moneda_fac', null);
                            $set('pendiente_fac', 0);
                            $set('monto_pago', 0);
                            //$set('factura', null);
                            $set('igeg_id', 0);
                        }
                    }),
                Hidden::make('tercero'),
                Hidden::make('moneda_fac')->default(0),
                Hidden::make('pendiente_fac')->default(0),
                Hidden::make('monto_pago')->default(0),
                Hidden::make('monto_pago_usd')->default(0),
                Hidden::make('tipo_cambio')->default(1.00),
                TextInput::make('numero_total')->label('Numero de Facturas')->numeric()->readOnly()->default(0),
                TextInput::make('monto_total')->label('Pagos Totales')->numeric()->currencyMask()->prefix('$')->readOnly()->default(0),
                Hidden::make('igeg_id'),
                TableRepeater::make('facturas_a_pagar')
                ->addable(false)
                ->reorderable(false)
                ->columnSpan(6)
                ->emptyLabel('No hay Registros')
                ->headers([
                    Header::make('Referencia'),
                    Header::make('Fecha'),
                    Header::make('Tercero'),
                    Header::make('Pendiente'),
                    Header::make('Moneda'),
                    Header::make('T. de Cambio'),
                    Header::make('Monto a Pagar'),
                    Header::make('USD a Pagar')
                ])
                ->schema([
                    TextInput::make('Referencia')->readOnly(),
                    DatePicker::make('Fecha')->readOnly(),
                    TextInput::make('Tercero')->readOnly(),
                    TextInput::make('Pendiente')->readOnly()->numeric()->currencyMask(precision: 6)->prefix('$'),
                    TextInput::make('Moneda')->readOnly(),
                    TextInput::make('Tipo Cambio')->numeric()->currencyMask(precision: 6)->prefix('$'),
                    TextInput::make('Monto a Pagar')->numeric()->currencyMask(precision: 6)->prefix('$')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $data_tmp = $get('../../facturas_a_pagar');
                            $sum = array_sum(array_column($data_tmp,'Monto a Pagar'));
                            $cnt = count($data_tmp);
                            $set('../../monto_total',$sum);
                        }),
                    TextInput::make('USD a Pagar')->readOnly()->numeric()->currencyMask()->prefix('$'),
                    Hidden::make('id_xml'),Hidden::make('id_fac'),Hidden::make('igeg_id_id')
                ])->afterStateUpdated(function (Get $get, Set $set) {
                        $data_tmp = $get('facturas_a_pagar');
                        $sum = array_sum(array_column($data_tmp,'Monto a Pagar'));
                        $cnt = count($data_tmp);
                        $set('numero_total',$cnt);
                        $set('monto_total',$sum);
                }),
                Actions::make([
                    Actions\Action::make('Aceptar')->icon('fas-save')
                    ->extraAttributes(['style'=>'width:10rem; margin-top: 2rem'])
                    ->action(function (Get $get) {
                        $res = $this->graba_poliza($get);
                        if($res > 0){
                            Notification::make()->title('Poliza Eg'.$res.' Generada')->success()->send();
                            return redirect(MovbancosResource::getUrl('index'));
                        }
                    })->requiresConfirmation(),
                    Actions\Action::make('Cancelar')->icon('fas-ban')
                    ->extraAttributes(['style'=>'width:10rem'])
                    ->url(MovbancosResource::getUrl('index'))
                    ->color(Color::Red)
                ])
            ])->columns(7)->statePath('data');
    }
    public function graba_poliza(Get $get)
    {
        $record = Movbancos::where('id', $this->record_id)->first();
        $moneda_pago = $get('moneda');
        $facturas = $get('facturas_a_pagar');
        $terceros = '';
        foreach ($this->nom_terceros as $tercero) {
            $terceros .= ' ' . $tercero;
        }
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $poliza = CatPolizas::create([
            'tipo' => 'Eg',
            'folio' => $nopoliza,
            'fecha' => $get('fecha'),
            'concepto' => $terceros,
            'cargos' => 0,
            'abonos' => 0,
            'periodo' => Filament::getTenant()->periodo,
            'ejercicio' => Filament::getTenant()->ejercicio,
            'referencia' => $this->fact_nombres,
            'uuid' => '',
            'tiposat' => 'Eg',
            'team_id' => Filament::getTenant()->id,
            'idmovb' => $this->record_id
        ]);
        $polno = $poliza['id'];
        $no_intera = 0;
        $id_cta_banco = 0;
        $id_cta_banco_com = 0;
        $mon_apli_ban = 0;
        $mon_apli_com = 0;
        $imp_dolares= 0;
        $imp_pesos= 0;
        foreach ($facturas as $factura) {
            if (floatval($factura['Monto a Pagar']) > 0) {
                $fac_id = $factura['id_xml'];
                $partida = 1;
                $igeg = IngresosEgresos::where('xml_id', $fac_id)->first();
                $fss = DB::table('almacencfdis')->where('id', $igeg->xml_id)->first();
                $ban = DB::table('banco_cuentas')->where('id', $this->datos_mov->cuenta)->first();
                $ban_com = CatCuentas::where('id', $ban->complementaria)->first();
                $ter = DB::table('terceros')->where('rfc', $fss->Emisor_Rfc)->first();
                $monto_par = 0;
                try {
                    $aplicar = floatval($factura['Monto a Pagar']);
                    $cxp_ =CuentasPagar::where('team_id', Filament::getTenant()->id)
                        ->where('refer', $fac_id)->first();
                    CuentasPagar::where('team_id', Filament::getTenant()->id)
                        ->where('refer', $fac_id)
                        ->decrement('saldo', $aplicar);
                    Proveedores::where('id', $cxp_->proveedor)->decrement('saldo', $factura['Monto a Pagar']);
                } catch (\Throwable $e) {
                    // Continuar sin interrumpir en caso de error
                    error_log($e->getMessage());
                }
                if ($factura['Moneda'] == 'MXN') $monto_par = floatval($factura['Monto a Pagar']);
                if ($factura['Moneda'] != 'MXN') $monto_par = floatval($factura['USD a Pagar']);

                if ($factura['Moneda'] == 'MXN' && $moneda_pago == 'MXN') {
                    if($no_intera == 0) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id' => $polno,
                            'codigo' => $ban->codigo,
                            'cuenta' => $ban->banco,
                            'concepto' => $fss->Emisor_Nombre,
                            'cargo' => 0,
                            'abono' => 0,
                            'factura' => $fss->Serie . $fss->Folio,
                            'nopartida' => $partida,
                            'team_id' => Filament::getTenant()->id,
                            'igeg_id' => $igeg->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id' => $aux['id'],
                            'cat_polizas_id' => $polno
                        ]);
                        $partida++;
                        $no_intera++;
                        $id_cta_banco = $aux['id'];
                        $mon_apli_ban += $monto_par;
                    }else{
                        $mon_apli_ban += $monto_par;
                    }
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $monto_par,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11801000',
                        'cuenta' => '-IVA acreditable pagado',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => (floatval($monto_par) / 1.16) * 0.16,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11901000',
                        'cuenta' => 'IVA pendiente de pago',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => 0,
                        'abono' => (floatval($monto_par) / 1.16) * 0.16,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;

                    $st_con = 'NO';
                    $n_pen = floatval($get('pendiente')) - floatval($monto_par);
                    $n_pen2 = floatval($factura['Pendiente']) - floatval($monto_par);
                    if ($n_pen < 0) $n_pen = 0;
                    if (floatval($get('pendiente')) <= floatval($monto_par)) $st_con = 'SI';
                    if (floatval($get('pendiente')) > floatval($monto_par)) $st_con = 'PA';
                    Movbancos::where('id', $this->record_id)->update([
                        'pendiente_apli' => $n_pen,
                        'contabilizada' => $st_con
                    ]);
                    IngresosEgresos::where('id', $fac_id)->update([
                        'pendientemxn' => $n_pen2
                    ]);
                }
                if ($factura['Moneda'] != 'MXN' && $moneda_pago == 'MXN') {
                    $pesos = floatval($monto_par) * floatval($get('tipo_cambio'));
                    $dolares = floatval($monto_par);
                    $tipoc_f = floatval($factura['Tipo Cambio']);
                    $tipoc = floatval($get('tipo_cambio'));
                    $complemento = (($dolares * $tipoc_f) - $dolares);
                    $iva_1 = ((($dolares / 1.16) * 0.16) * $tipoc);
                    $iva_2 = ((($dolares / 1.16) * 0.16) * $tipoc_f);
                    $importe_cargos = $dolares + $complemento + $iva_1;
                    $importe_abonos = $pesos + $iva_2;
                    $uti_per = $importe_cargos - $importe_abonos;
                    $importe_abonos_f = $pesos + $iva_2 + $uti_per;
                    $imp_uti_c = 0;
                    $imp_uti_a = 0;
                    $cod_uti = '';
                    $cta_uti = '';
                    if ($uti_per > 0) {
                        $imp_uti_c = 0;
                        $imp_uti_a = $uti_per;
                        $cod_uti = '70201000';
                        $cta_uti = 'Perdida Cambiaria';
                    } else {
                        $imp_uti_a = 0;
                        $imp_uti_c = $uti_per * -1;
                        $cod_uti = '70101000';
                        $cta_uti = 'Utilidad Cambiaria';
                    }

                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $dolares,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $complemento,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    if($no_intera == 0) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id' => $polno,
                            'codigo' => $ban->codigo,
                            'cuenta' => $ban->banco,
                            'concepto' => $fss->Emisor_Nombre,
                            'cargo' => 0,
                            'abono' => $pesos,
                            'factura' => $fss->Serie . $fss->Folio,
                            'nopartida' => $partida,
                            'team_id' => Filament::getTenant()->id,
                            'igeg_id' => $igeg->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id' => $aux['id'],
                            'cat_polizas_id' => $polno
                        ]);
                        $partida++;
                        $no_intera++;
                        $id_cta_banco = $aux['id'];
                        $mon_apli_ban += $monto_par;
                    }else{
                        $mon_apli_ban += $monto_par;
                    }
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11801000',
                        'cuenta' => 'IVA acreditable pagado',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $iva_1,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11901000',
                        'cuenta' => 'IVA pendiente de pago',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => 0,
                        'abono' => $iva_2,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $cod_uti,
                        'cuenta' => $cta_uti,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $imp_uti_c,
                        'abono' => $imp_uti_a,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $st_con = 'NO';
                    $n_pen = floatval($get('pendiente')) - floatval($monto_par);
                    $n_pen2 = floatval($factura['Pendiente']) - floatval($monto_par);
                    if ($n_pen < 0) $n_pen = 0;
                    if (floatval($get('pendiente')) <= floatval($monto_par)) $st_con = 'SI';
                    if (floatval($get('pendiente')) > floatval($monto_par)) $st_con = 'PA';
                    Movbancos::where('id', $this->record_id)->update([
                        'pendiente_apli' => $n_pen,
                        'contabilizada' => $st_con
                    ]);
                    IngresosEgresos::where('id', $fac_id)->update([
                        'pendientemxn' => $n_pen2
                    ]);
                }
                if ($factura['Moneda'] != 'MXN' && $moneda_pago != 'MXN') {
                    $cta_ban = BancoCuentas::where('id',$record->cuenta)->first();
                    $cta_comple = CatCuentas::where('id',$cta_ban->complementaria)->first();
                    //dd($cta_comple);
                    $pesos = floatval($monto_par) * floatval($factura['Tipo Cambio']);
                    $dolares = floatval($monto_par);
                    $tipoc_f = floatval($factura['Tipo Cambio']);
                    $tipoc = floatval($record->tcambio);
                    $cfdi  = Almacencfdis::where('id', $fac_id)->first();
                    $iva_fac = floatval($cfdi->TotalImpuestosTrasladados);
                    //dd($factura,$cfdi->TotalImpuestosTrasladados);
                    $complemento = (($dolares * $tipoc_f) - $dolares);
                    $iva_1 = $iva_fac * $tipoc;
                    $iva_2 = $iva_fac * $tipoc_f;
                    $importe_cargos = $dolares + $complemento + $iva_1;
                    $importe_abonos = $pesos + $iva_2;
                    ///------Calcula Utilidad---------------------------------------
                    $uti_1 = floatval($cfdi->Total) * floatval($record->tcambio);
                    $uti_2 = floatval($cfdi->Total) * floatval($cfdi->TipoCambio);
                    $uti_per = $uti_1 - $uti_2 + $iva_2 - $iva_1;
                    //--------------------------------------------------------------
                    //$uti_per = $iva_1 - $iva_2;
                    $importe_abonos_f = $pesos + $iva_2 + $uti_per;
                    $imp_uti_c = 0;
                    $imp_uti_a = 0;
                    $cod_uti = '';
                    $cta_uti = '';
                    if ($uti_per > 0) {
                        $imp_uti_c = $uti_per;
                        $imp_uti_a = 0;
                        $cod_uti = '70201000';
                        $cta_uti = 'Utilidad Cambiaria';
                    } else {
                        $imp_uti_a = $uti_per * -1;
                        $imp_uti_c = 0;
                        $cod_uti = '70101000';
                        $cta_uti = 'Perdida Cambiaria';
                    }
                    if($no_intera == 0) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id' => $polno,
                            'codigo' => $ban->codigo,
                            'cuenta' => $ban->banco,
                            'concepto' => $fss->Emisor_Nombre,
                            'cargo' => 0,
                            'abono' => 0,
                            'factura' => $this->fact_nombres,
                            'nopartida' => $partida,
                            'team_id' => Filament::getTenant()->id,
                            'igeg_id' => $igeg->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id' => $aux['id'],
                            'cat_polizas_id' => $polno
                        ]);
                        $id_cta_banco = $aux['id'];
                        $partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id' => $polno,
                            'codigo' => $cta_comple->codigo,
                            'cuenta' => $cta_comple->nombre,
                            'concepto' => $fss->Emisor_Nombre,
                            'cargo' => 0,
                            'abono' => 0,
                            'factura' => $this->fact_nombres,
                            'nopartida' => $partida,
                            'team_id' => Filament::getTenant()->id,
                            'igeg_id' => $igeg->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id' => $aux['id'],
                            'cat_polizas_id' => $polno
                        ]);
                        $id_cta_banco_com = $aux['id'];
                        $partida++;

                        $mon_apli_ban+= $dolares;
                        $imp_dolares+= $dolares;
                        $imp_pesos+= $pesos;
                        $mon_apli_com+= $pesos;
                        $no_intera++;
                    }else{
                        $mon_apli_ban+= $dolares;
                        $imp_dolares+= $dolares;
                        $imp_pesos+= $pesos;
                        $mon_apli_com+= $pesos;
                    }
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11801000',
                        'cuenta' => 'IVA acreditable pagado',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $iva_1,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11901000',
                        'cuenta' => 'IVA pendiente de pago',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => 0,
                        'abono' => $iva_2,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' =>$partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $dolares,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $complemento,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $cod_uti,
                        'cuenta' => $cta_uti,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $imp_uti_c,
                        'abono' => $imp_uti_a,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $st_con = 'NO';
                    $n_pen = floatval($get('pendiente')) - floatval($monto_par);
                    $n_pen2 = floatval($factura['Pendiente']) - floatval($monto_par);
                    if ($n_pen < 0) $n_pen = 0;
                    if (floatval($get('pendiente')) <= floatval($monto_par)) $st_con = 'SI';
                    if (floatval($get('pendiente')) > floatval($monto_par)) $st_con = 'PA';
                    Movbancos::where('id', $this->record_id)->update([
                        'pendiente_apli' => $n_pen,
                        'contabilizada' => $st_con
                    ]);
                    IngresosEgresos::where('id', $fac_id)->update([
                        'pendientemxn' => $n_pen2
                    ]);
                }

                if ($factura['Moneda'] == 'MXN' && $moneda_pago != 'MXN') {
                    $pesos = floatval($monto_par) * floatval($factura['Tipo Cambio']);
                    $dolares = floatval($monto_par);
                    $tipoc_f = floatval($factura['Tipo Cambio']);
                    $tipoc = floatval($get('tipo_cambio'));
                    $complemento = (($dolares * $tipoc) - $dolares);
                    $iva_1 = ((($dolares / 1.16) * 0.16) * $tipoc);
                    $iva_2 = ((($dolares / 1.16) * 0.16) * $tipoc_f);
                    $importe_cargos = $dolares + $complemento + $iva_1;
                    $importe_abonos = $pesos + $iva_2;
                    $uti_per = $importe_cargos - $importe_abonos;
                    $importe_abonos_f = $pesos + $iva_2 + $uti_per;
                    $imp_uti_c = 0;
                    $imp_uti_a = 0;
                    $cod_uti = '';
                    $cta_uti = '';
                    if ($uti_per > 0) {
                        $imp_uti_c = 0;
                        $imp_uti_a = $uti_per;
                        $cod_uti = '70201000';
                        $cta_uti = 'Utilidad Cambiaria';
                    } else {
                        $imp_uti_a = 0;
                        $imp_uti_c = $uti_per * -1;
                        $cod_uti = '70101000';
                        $cta_uti = 'Perdida Cambiaria';
                    }
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $dolares,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $ter->cuenta,
                        'cuenta' => $ter->nombre,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $complemento,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11801000',
                        'cuenta' => 'IVA acreditable pagado',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $iva_1,
                        'abono' => 0,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '11901000',
                        'cuenta' => 'IVA pendiente de pago',
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => 0,
                        'abono' => $iva_2,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    if($no_intera == 0) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id' => $polno,
                            'codigo' => $ban->codigo,
                            'cuenta' => $ban->banco,
                            'concepto' => $fss->Emisor_Nombre,
                            'cargo' => 0,
                            'abono' => $pesos,
                            'factura' => $fss->Serie . $fss->Folio,
                            'nopartida' => $partida,
                            'team_id' => Filament::getTenant()->id,
                            'igeg_id' => $igeg->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id' => $aux['id'],
                            'cat_polizas_id' => $polno
                        ]);
                        $partida++;
                        $id_cta_banco = $aux['id'];
                        $mon_apli_ban += $monto_par;
                    }else{
                        $mon_apli_ban += $monto_par;
                    }
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => $cod_uti,
                        'cuenta' => $cta_uti,
                        'concepto' => $fss->Emisor_Nombre,
                        'cargo' => $imp_uti_c,
                        'abono' => $imp_uti_a,
                        'factura' => $fss->Serie . $fss->Folio,
                        'nopartida' => $partida,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $partida++;
                    $st_con = 'NO';
                    $n_pen = floatval($get('pendiente')) - floatval($monto_par);
                    $n_pen2 = floatval($factura['Pendiente']) - floatval($monto_par);
                    if ($n_pen < 0) $n_pen = 0;
                    if (floatval($get('pendiente')) <= floatval($monto_par)) $st_con = 'SI';
                    if (floatval($get('pendiente')) > floatval($monto_par)) $st_con = 'PA';
                    Movbancos::where('id', $this->record_id)->update([
                        'pendiente_apli' => $n_pen,
                        'contabilizada' => $st_con
                    ]);
                    IngresosEgresos::where('id', $fac_id)->update([
                        'pendientemxn' => $n_pen2
                    ]);

                    // Aplicar pago a Cuenta por Pagar y disminuir saldo del proveedor
                    try {
                        $cxp = CuentasPagar::where('team_id', Filament::getTenant()->id)
                            ->where(function($q) use ($igeg, $fss) {
                                $q->where('refer', $igeg->xml_id)
                                  ->orWhere('documento', ($fss->Serie . $fss->Folio));
                            })
                            ->first();
                        if ($cxp) {
                            $aplicar = min(max(0, (float) $monto_par), (float) $cxp->saldo);
                            if ($aplicar > 0) {
                                // Actualiza saldo de la CxP
                                $nuevoSaldo = max(0, (float) $cxp->saldo - $aplicar);
                                CuentasPagar::where('id', $cxp->id)->update(['saldo' => $nuevoSaldo]);
                                // Disminuye saldo del proveedor
                                Proveedores::where('id', $cxp->proveedor)->decrement('saldo', $aplicar);
                            }
                        }
                    } catch (\Throwable $e) {
                        // En caso de error, continuamos sin detener el flujo de contabilización
                    }
                }
            }
        }
        Auxiliares::where('id', $id_cta_banco)->update(['abono' => $mon_apli_ban]);
        if($id_cta_banco_com > 0) {
            $n_imp = ($record->importe * $record->tcambio) - $record->importe;
            Auxiliares::where('id', $id_cta_banco_com)->update(['abono' => $n_imp]);
        }


        return $nopoliza;
    }
    public function graba_mov(Get $get)
    {
        $record = $this->datos_mov;
        $data_tmp = $get('facturas_a_pagar');
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->first();
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $poliza = CatPolizas::create([
            'tipo' => 'Eg',
            'folio' => $nopoliza,
            'fecha' => $record->fecha,
            'concepto' => 'Pagos a Facturas',
            'cargos' => $get('monto_total'),
            'abonos' => $get('monto_total'),
            'periodo' => Filament::getTenant()->periodo,
            'ejercicio' => Filament::getTenant()->ejercicio,
            'referencia' => 'Pagos a Facturas '.Carbon::now()->format('d/m/Y'),
            'uuid' => '',
            'tiposat' => 'Eg',
            'team_id' => Filament::getTenant()->id,
            'idmovb' => $record->id
        ]);
        $polno = $poliza['id'];
        $partida = 1;
        foreach ($data_tmp as $data) {
            $fss = DB::table('almacencfdis')->where('id',$data['id_xml'])->first();
            $ter = DB::table('terceros')->where('rfc',$fss->Emisor_Rfc)->first();
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter->cuenta,
                'cuenta'=>$ter->nombre,
                'concepto'=>$fss->Emisor_Nombre,
                'cargo'=>$data['Monto a Pagar'],
                'abono'=>0,
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$partida,
                'team_id'=>Filament::getTenant()->id,
                'igeg_id'=>$data['igeg_id_id']
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $partida++;
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11801000',
                'cuenta'=>'-IVA acreditable pagado',
                'concepto'=>$fss->Emisor_Nombre,
                'cargo'=>(floatval($data['Monto a Pagar']) / 1.16) * 0.16,
                'abono'=>0,
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$partida,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $partida++;
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>$fss->Emisor_Nombre,
                'cargo'=>0,
                'abono'=>(floatval($data['Monto a Pagar']) / 1.16) * 0.16,
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$partida,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $partida++;
            $n_pen2 = floatval($data['Pendiente']) - floatval($data['Monto a Pagar']);
            IngresosEgresos::where('id',$data['id_fac'])->update([
                'pendientemxn' => $n_pen2
            ]);

            // Aplicar pago a CxP y disminuir saldo del proveedor

        }
        $aux = Auxiliares::create([
            'cat_polizas_id'=>$polno,
            'codigo'=>$ban->codigo,
            'cuenta'=>$ban->banco,
            'concepto'=>'Pagos a Facturas',
            'cargo'=>0,
            'abono'=>$get('monto_total'),
            'factura'=>'Pagos a Facturas',
            'nopartida'=>$partida,
            'team_id'=>Filament::getTenant()->id
        ]);
        DB::table('auxiliares_cat_polizas')->insert([
            'auxiliares_id'=>$aux['id'],
            'cat_polizas_id'=>$polno
        ]);
        $partida++;
        $n_pen = floatval($get('pendiente')) - floatval($get('monto_total'));

        Movbancos::where('id',$record->id)->update([
            'pendiente_apli'=>$n_pen,
            'contabilizada'=>'SI'
        ]);
        return 'Grabado';
    }
    public function FacturasGet(): array
    {
        $ing_ret = IngresosEgresos::where('team_id', Filament::getTenant()->id)->where('tipo', 0)->where('pendientemxn', '>', 0)->get();
        $data = array();
        foreach ($ing_ret as $item) {
            $alm = Almacencfdis::where('id', $item->xml_id)->first();

            if ($item->tcambio > 1) {
                $monea = 'USD';
                $tot = '$' . number_format(($item->totalusd), 2);
                $pend = '$' . number_format(($item->pendienteusd), 2);
            }else{
                $monea = 'MXN';
                $tot = '$' . number_format($item->totalmxn, 2);
                $pend = '$' . number_format($item->pendientemxn, 2);
            }
            $data += [
                $item->id . '|' . $item->xml_id =>
                    'Tercero: ' . $alm->Emisor_Nombre . ' |' .
                    'Referencia: ' . $item->referencia . ' |' .
                    'Importe: ' . $tot . ' |' .
                    'Pendiente: ' . $pend . ' |' .
                    'Moneda: ' . $monea
            ];
        }
        return $data;
    }
}
