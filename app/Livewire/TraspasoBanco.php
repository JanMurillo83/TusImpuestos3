<?php

namespace App\Livewire;

use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\Movbancos;
use App\Models\RegTraspasos;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TraspasoBanco extends Widget implements HasForms
{
    use InteractsWithForms;
    protected static string $view = 'livewire.traspaso-banco';
    public $record;
    public $fecha;
    public $cuenta;
    public $cuenta_id;
    public $importe;
    public $concepto;
    public $moneda;
    public $tcambio;
    public $importe_d = 0;
    public $concepto_d = '';
    public $moneda_d = '';
    public $tcambio_d = 0;
    public $tcambio_d_o = 0;
    public $cuentas_contable_origen;
    public $cuentas_complementaria_origen;
    public $cuenta_destino = '';
    public $cuenta_destino_id = '';
    public $movimiento_destino = '';
    public $movimiento_origen = '';
    public function mount($record): void
    {
        $this->record = $record;
        $this->movimiento_origen = $record->id;
        $this->fecha = $record->fecha;
        $cta = BancoCuentas::where('id',$record->cuenta)->first();
        $this->cuenta_id = $record->cuenta;
        $this->cuenta = $cta->banco;
        $this->importe = $record->importe;
        $this->concepto = $record->concepto;
        $this->moneda = $record->moneda;
        $this->tcambio = $record->tcambio;
        $this->cuentas_contable_origen = $cta->codigo;
        $this->cuentas_complementaria_origen = $cta->complementaria;

    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Cuenta Origen')
                ->schema([
                    TextInput::make('fecha')->default(Carbon::create(substr($this->fecha,0,10))->format('Y-m-d'))->readOnly(),
                    TextInput::make('cuenta')->default($this->cuenta)->readOnly(),
                    TextInput::make('moneda')->default($this->moneda)->readOnly(),
                    TextInput::make('tcambio')->default($this->tcambio)->currencyMask(precision: 4)->prefix('$')->label('T.Cambio')->readOnly(),
                    TextInput::make('importe')->default($this->importe)->currencyMask()->prefix('$')->readOnly(),
                    TextInput::make('concepto')->default($this->concepto)->readOnly()->columnSpan(2),
                ])->columnSpanFull()->columns(5),
                Fieldset::make('Cuenta Destino')
                ->schema([
                    Select::make('cuenta_destino')->label('Cuenta Destino')
                        ->options(BancoCuentas::all()->pluck('banco','id'))
                        ->searchable()->required()->columnSpan(2)->live(onBlur: true),
                    Select::make('movimiento_destino')->label('Movimiento Destino')
                        ->searchable()->required()
                    ->disabled(function (Get $get){
                        if($get('cuenta_destino') != '') return false;
                        return true;
                    })
                    ->options(function (Get $get){
                        return Movbancos::where('cuenta',$get('cuenta_destino'))
                            ->select(DB::raw("concat(concepto,'  -$',FORMAT(importe, 2)) as movimiento"),'id')
                            ->where('tipo','E')
                            ->pluck('movimiento','id');
                    })->columnSpan(3)->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, $set){
                        $mov = Movbancos::where('id',$get('movimiento_destino'))->first();
                        $set('moneda_d',$mov->moneda);
                        //dd($mov->importe,$this->importe);
                        $importe_d_d = floatval($mov->importe)  / floatval($this->importe ?? 1);
                        $set('tcambio_d',$importe_d_d);
                        $set('tcambio_d_o',$mov->tcambio);
                        $set('importe_d',$mov->importe);
                        $set('concepto_d',$mov->concepto);
                    }),
                    TextInput::make('moneda_d')->default($this->moneda)->readOnly()->label('Moneda'),
                    TextInput::make('tcambio_d')->default($this->tcambio)->currencyMask(precision: 4)->prefix('$')->label('T.Cambio')->label('T.Cambio')->required(),
                    TextInput::make('importe_d')->default($this->importe)->currencyMask()->prefix('$')->readOnly()->label('Importe'),
                    Hidden::make('concepto_d')->default($this->concepto),
                    Hidden::make('tcambio_d_o')->default($this->tcambio),
                    Actions::make([
                        Actions\Action::make('Guardar')
                        ->label('Guardar')->color('success')
                        ->icon('fas-save')
                        ->action(function (Get $get,$action){
                            $mon_o = $get('moneda');
                            $mon_d = $get('moneda_d');
                            $tc_o = $get('tcambio');
                            $tc_d = $get('tcambio_d');
                            $tc_d_o = $get('tcambio_d_o');
                            $imp_o = $get('importe');
                            $imp_d = $get('importe_d');
                            $cta_o = $get('cuenta');
                            $cta_d = $get('cuenta_destino');
                            $mov_o = $get('movimiento_destino');
                            $mov_d = $get('movimiento_destino');
                            $dat_cta_or = BancoCuentas::where('id',$this->cuenta_id)->first();
                            //dd($dat_cta_or);
                            $dat_cta_de = BancoCuentas::where('id',$cta_d)->first();
                            //dd($dat_cta_or->codigo,$dat_cta_or->banco,$dat_cta_de->codigo,$dat_cta_de->banco);
                            $fecha= Carbon::create(substr($get('fecha'),0,10))->format('Y-m-d');
                            $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                            $poliza = CatPolizas::create([
                                'tipo'=>'Dr',
                                'folio'=>$nopoliza,
                                'fecha'=>$fecha,
                                'concepto'=>'Traspaso entre Cuentas',
                                'cargos'=>0,
                                'abonos'=>0,
                                'periodo'=>Filament::getTenant()->periodo,
                                'ejercicio'=>Filament::getTenant()->ejercicio,
                                'referencia'=>'F-',
                                'uuid'=>'',
                                'tiposat'=>'Dr',
                                'team_id'=>Filament::getTenant()->id,
                                'idmovb'=>0
                            ]);
                            $polno = $poliza['id'];
                            $par_num = 1;
                            if($mon_o == $mon_d){
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_de->codigo,
                                    'cuenta'=>$dat_cta_de->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($imp_o),2),
                                    'abono'=>0,
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_or->codigo,
                                    'cuenta'=>$dat_cta_or->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>0,
                                    'abono'=>round(floatval($imp_o),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                CatPolizas::where('id',$polno)->update([
                                    'cargos'=>round(floatval($imp_o),2),
                                    'abonos'=>round(floatval($imp_o),2),
                                ]);
                            }

                            if($mon_o != $mon_d&&$mon_o == 'USD'){
                                $imp_usd = $imp_o * $tc_o;
                                $imp_mxn = $imp_o * $tc_d_o;
                                $dif = $imp_mxn - $imp_usd;
                                $gan_per_c = 0;
                                $gan_per_a = 0;
                                $cta_ganper = '';
                                $cta_ganper_con = '';
                                if($dif > 0) {
                                    $cta_ganper = '70201000';
                                    $cta_ganper_con = 'Utilidad Cambiaria';
                                    $gan_per_c = $dif;
                                    $gan_per_a = 0;
                                }else{
                                    $cta_ganper = '70101000';
                                    $cta_ganper_con = 'Perdida Cambiaria';
                                    $gan_per_c = 0;
                                    $gan_per_a = $dif*-1;
                                }
                                $imp_com = $imp_mxn- $imp_o;
                                $cta_comple = CatCuentas::where('id',$dat_cta_or->complementaria)->first();
                                $t1_cargos = 0;
                                $t1_abonos = 0;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_de->codigo,
                                    'cuenta'=>$dat_cta_de->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($imp_d),2),
                                    'abono'=>0,
                                    'factura'=>'F',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= round(floatval($imp_o),2);
                                $t1_abonos+= 0;
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_or->codigo,
                                    'cuenta'=>$dat_cta_or->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>0,
                                    'abono'=>round(floatval($imp_o),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= 0;
                                $t1_abonos+= round(floatval($imp_o),2);
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$cta_comple->codigo,
                                    'cuenta'=>$cta_comple->nombre,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>0,
                                    'abono'=>round(floatval($imp_com),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= 0;
                                $t1_abonos+= round(floatval($imp_com),2);
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$cta_ganper,
                                    'cuenta'=>$cta_ganper_con,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($gan_per_c),2),
                                    'abono'=>round(floatval($gan_per_a),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= round(floatval($gan_per_c),2);
                                $t1_abonos+= round(floatval($gan_per_a),2);
                                CatPolizas::where('id',$polno)->update([
                                    'cargos'=>round(floatval($t1_cargos),2),
                                    'abonos'=>round(floatval($t1_abonos),2),
                                ]);
                            }
                            if($mon_o != $mon_d&&$mon_o == 'MXN'){
                                $imp_usd = $imp_d * $tc_d_o;
                                $imp_mxn = $imp_d * $tc_d;
                                $dif = $imp_mxn - $imp_usd;
                                $gan_per_c = 0;
                                $gan_per_a = 0;
                                $cta_ganper = '';
                                $cta_ganper_con = '';
                                $t1_cargos = 0;
                                $t1_abonos = 0;
                                if($dif > 0) {
                                    $cta_ganper = '70201000';
                                    $cta_ganper_con = 'Utilidad Cambiaria';
                                    $gan_per_c = $dif;
                                    $gan_per_a = 0;
                                }else{
                                    $cta_ganper = '70101000';
                                    $cta_ganper_con = 'Perdida Cambiaria';
                                    $gan_per_c = 0;
                                    $gan_per_a = $dif * -1;
                                }
                                $imp_com = $imp_mxn - $imp_d;
                                $cta_comple = CatCuentas::where('codigo',$dat_cta_de->complementaria)->first();
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_de->codigo,
                                    'cuenta'=>$dat_cta_de->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($imp_d),2),
                                    'abono'=>0,
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= round(floatval($imp_d),2);
                                $t1_abonos+= 0;
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$cta_comple->codigo,
                                    'cuenta'=>$cta_comple->nombre,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($imp_com),2),
                                    'abono'=>0,
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= round(floatval($imp_com),2);
                                $t1_abonos+= 0;
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$dat_cta_or->codigo,
                                    'cuenta'=>$dat_cta_or->banco,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>0,
                                    'abono'=>round(floatval($imp_o),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= 0;
                                $t1_abonos+= round(floatval($imp_o),2);
                                $par_num++;
                                $aux = Auxiliares::create([
                                    'cat_polizas_id'=>$polno,
                                    'codigo'=>$cta_ganper,
                                    'cuenta'=>$cta_ganper_con,
                                    'concepto'=>'Traspaso entre Cuentas',
                                    'cargo'=>round(floatval($gan_per_c),2),
                                    'abono'=>round(floatval($gan_per_a),2),
                                    'factura'=>'F-',
                                    'nopartida'=>$par_num,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                                DB::table('auxiliares_cat_polizas')->insert([
                                    'auxiliares_id'=>$aux['id'],
                                    'cat_polizas_id'=>$polno
                                ]);
                                $t1_cargos+= round(floatval($gan_per_c),2);
                                $t1_abonos+= round(floatval($gan_per_a),2);
                                CatPolizas::where('id',$polno)->update([
                                    'cargos'=>round(floatval($t1_cargos),2),
                                    'abonos'=>round(floatval($t1_abonos),2),
                                ]);
                            }
                            Movbancos::where('id',$get('movimiento_destino'))->update(['contabilizada'=>'SI']);
                            Movbancos::where('id',$this->movimiento_origen)->update(['contabilizada'=>'SI']);
                            RegTraspasos::create([
                                'periodo'=>Filament::getTenant()->periodo,
                                'ejercicio'=>Filament::getTenant()->ejercicio,
                                'mov_ent'=>$get('movimiento_destino'),
                                'mov_sal'=>$this->movimiento_origen,
                                'poliza'=>$polno,
                                'team_id'=>Filament::getTenant()->id
                            ]);
                            Notification::make()->title('Traspaso de Cuentas')->success()->body('Se ha registrado el traspaso de cuentas Póliza Dr'.$nopoliza)->send();
                            $action->success();
                        })
                    ])

                ])->columnSpanFull()->columns(5)
            ]);
    }

}

