<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use App\Http\Controllers\DescargaSAT;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\IngresosEgresos;
use App\Models\Movbancos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
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
use Illuminate\Support\Facades\DB;

class Cobros extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = MovbancosResource::class;

    protected static string $view = 'filament.resources.movbancos-resource.pages.cobros';

    public ?array $data = [];
    public ?string $fecha = null;
    public ?string $monto = null;
    public ?string $pendiente = null;
    public ?string $concepto = null;
    public ?string $cuenta = null;
    public ?string $moneda = null;
    public ?string $factura = null;
    public ?string $igeg_id = null;
    public ?string $numero_total = null;
    public ?string $monto_total = null;
    public ?string $monto_pago = null;
    public ?string $monto_pago_usd = null;
    public ?string $tipo_cambio = null;
    public ?array $facturas_a_pagar = null;
    public $datos_mov;

    public function mount($record) :void
    {
        $datos = Movbancos::where('id',$record)->first();
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
                Fieldset::make('Datos del Cobro')
                    ->schema([
                        DatePicker::make('fecha')->readOnly(),
                        TextInput::make('monto')->numeric()->currencyMask()->prefix('$')->readOnly(),
                        TextInput::make('pendiente')->numeric()->currencyMask()->prefix('$')->readOnly(),
                        TextInput::make('moneda')->readOnly(),
                        TextInput::make('concepto')->columnSpan(2)->readOnly(),
                        Hidden::make('cuenta'),

                    ])->columnSpanFull()->columns(6),
                Select::make('factura')
                    ->searchable()
                    ->disabled(function (Get $get){
                        $imp1 = $get('pendiente');
                        $imp2 = $get('monto_total');
                        if($imp1 <= $imp2) return true;
                        else return false;
                    })
                    ->columnSpan(4)
                    ->options(fn():array=>$this->FacturasGet())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get,Set $set){
                        if($get('factura')) {
                            $ineg = IngresosEgresos::where('id', $get('factura'))->first();
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
                        }
                    }),
                Hidden::make('igeg_id'),
                TextInput::make('tercero')->readOnly(),
                TextInput::make('moneda_fac')->readOnly(),
                TextInput::make('pendiente_fac')->label('Pendiente de Pago')->numeric()->currencyMask()->prefix('$')->readOnly(),
                TextInput::make('monto_pago')->label('Monto a Pagar MXN')->numeric()->currencyMask()->prefix('$')->default(0),
                TextInput::make('monto_pago_usd')->label('Monto a Pagar USD')->numeric()->currencyMask()->prefix('$')->default(0)->readOnly(),
                TextInput::make('tipo_cambio')->label('Tipo de Cambio')->numeric()->currencyMask()->prefix('$')->default(1.00)->readOnly(),
                TextInput::make('numero_total')->label('Numero de Facturas')->numeric()->readOnly()->default(0),
                TextInput::make('monto_total')->label('Pagos Totales')->numeric()->currencyMask()->prefix('$')->readOnly()->default(0),
                Actions::make([
                    Actions\Action::make('Agregar')->icon('fas-plus')->color(Color::Green)
                        ->action(function (Get $get,Set $set) {
                            $ineg = IngresosEgresos::where('id',$get('factura'))->first();
                            $fact = Almacencfdis::where('id',$ineg->xml_id)->first();
                            $data_tmp = $get('facturas_a_pagar');
                            $fec = explode('T',$fact->Fecha);
                            $fecha = $fec[0];
                            $fac_id = explode('|',$get('factura'))[0];
                            $data_new =  [
                                'Referencia'=>$fact->Serie.$fact->Folio,
                                'Fecha'=>$fecha,
                                'Tercero'=>$fact->Emisor_Nombre,
                                'Pendiente'=>$ineg->pendientemxn,
                                'Monto a Pagar'=>$get('monto_pago'),
                                'id_xml'=>$fact->id,
                                'id_fac'=>$fac_id,
                                'igeg_id_id'=>$ineg->id,
                            ];
                            array_push($data_tmp, $data_new );
                            $sum = array_sum(array_column($data_tmp,'Monto a Pagar'));
                            $cnt = count($data_tmp);
                            $set('numero_total',$cnt);
                            $set('monto_total',$sum);
                            $set('facturas_a_pagar',$data_tmp);
                            $set('tercero', null);
                            $set('moneda_fac', null);
                            $set('pendiente_fac', 0);
                            $set('monto_pago', 0);
                            $set('factura', null);
                            $set('igeg_id', 0);
                        })->extraAttributes(['style'=>'margin-top:2rem']),
                ]),
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
                    Header::make('Monto a Pagar'),
                ])
                ->schema([
                    TextInput::make('Referencia')->readOnly(),
                    DatePicker::make('Fecha')->readOnly(),
                    TextInput::make('Tercero')->readOnly(),
                    TextInput::make('Pendiente')->readOnly()->numeric()->currencyMask()->prefix('$'),
                    TextInput::make('Monto a Pagar')->readOnly()->numeric()->currencyMask()->prefix('$'),
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
                        $res = $this->graba_mov($get);
                        if($res === 'Grabado'){
                            Notification::make()
                                ->title('Registro Grabado')
                                ->success()
                                ->send();
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

    public function graba_mov(Get $get)
    {
        $record = $this->datos_mov;
        $data_tmp = $get('facturas_a_pagar');
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->first();
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $poliza = CatPolizas::create([
            'tipo' => 'Ig',
            'folio' => $nopoliza,
            'fecha' => $record->fecha,
            'concepto' => 'Pagos a Facturas',
            'cargos' => $get('monto_total'),
            'abonos' => $get('monto_total'),
            'periodo' => Filament::getTenant()->periodo,
            'ejercicio' => Filament::getTenant()->ejercicio,
            'referencia' => 'Pagos a Facturas '.Carbon::now()->format('d/m/Y'),
            'uuid' => '',
            'tiposat' => 'Ig',
            'team_id' => Filament::getTenant()->id,
            'idmovb' => $record->id
        ]);
        $polno = $poliza['id'];
        $cnt_par = 1;
        foreach ($data_tmp as $data) {
            $fss = DB::table('almacencfdis')->where('id',$data['id_xml'])->first();
            $ter = DB::table('terceros')->where('rfc',$fss->Emisor_Rfc)->first();
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter->cuenta,
                'cuenta'=>$ter->nombre,
                'concepto'=>$fss->Receptor_Nombre,
                'cargo'=>0,
                'abono'=>$data['Monto a Pagar'],
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$cnt_par,
                'team_id'=>Filament::getTenant()->id,
                'igeg_id'=>$data['igeg_id_id']
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $cnt_par++;
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'20901000',
                'cuenta'=>'IVA trasladado no cobrado',
                'concepto'=>$fss->Receptor_Nombre,
                'cargo'=>(floatval($data['Monto a Pagar']) / 1.16) * 0.16,
                'abono'=>0,
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$cnt_par,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $cnt_par++;
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'20801000',
                'cuenta'=>'IVA trasladado cobrado',
                'concepto'=>$fss->Receptor_Nombre,
                'cargo'=>0,
                'abono'=>(floatval($data['Monto a Pagar']) / 1.16) * 0.16,
                'factura'=>$fss->Serie . $fss->Folio,
                'nopartida'=>$cnt_par,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $cnt_par++;
            $n_pen2 = floatval($data['Pendiente']) - floatval($data['Monto a Pagar']);
            IngresosEgresos::where('id',$data['id_fac'])->update([
                'pendientemxn' => $n_pen2
            ]);
        }
        $aux = Auxiliares::create([
            'cat_polizas_id'=>$polno,
            'codigo'=>$ban->codigo,
            'cuenta'=>$ban->banco,
            'concepto'=>'Cobros a Facturas',
            'cargo'=>$get('monto_total'),
            'abono'=>0,
            'factura'=>'Cobros a Facturas',
            'nopartida'=>$cnt_par,
            'team_id'=>Filament::getTenant()->id
        ]);
        DB::table('auxiliares_cat_polizas')->insert([
            'auxiliares_id'=>$aux['id'],
            'cat_polizas_id'=>$polno
        ]);
        $n_pen = floatval($get('pendiente')) - floatval($get('monto_total'));

        Movbancos::where('id',$record->id)->update([
            'pendiente_apli'=>$n_pen,
            'contabilizada'=>'SI'
        ]);
        return 'Grabado';
    }
    public function FacturasGet(): array
    {
        $ing_ret = IngresosEgresos::where('team_id', Filament::getTenant()->id)->where('tipo', 1)->where('pendientemxn', '>', 0)->get();
        foreach ($ing_ret as $item) {
            $alm = Almacencfdis::where('id', $item->xml_id)->first();
            $data = array();
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
                    'Tercero: ' . $alm->Receptor_Nombre . ' |' .
                    'Referencia: ' . $item->referencia . ' |' .
                    'Importe: ' . $tot . ' |' .
                    'Pendiente: ' . $pend . ' |' .
                    'Moneda: ' . $monea
            ];
        }
        return $data;
    }
}
