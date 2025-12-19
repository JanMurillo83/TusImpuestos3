<?php

namespace App\Filament\Pages;

use App\Http\Controllers\MainChartsController;
use App\Http\Controllers\ReportesController;
use App\Models\Auxiliares;
use App\Models\DatosFiscales;
use App\Models\EstadCXC;
use App\Models\EstadCXC_F;
use App\Models\EstadCXP_F;
use App\Models\Inventario;
use App\Models\SaldosReportes;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class DashBoardIndicadores extends Page
{
    protected static ?string $navigationIcon = 'fas-chart-line';

    protected static string $view = 'filament.pages.dash-board-indicadores';
    protected static ?string $navigationLabel = 'Indicadores';
    protected ?string $maxContentWidth = 'full';
    public function getTitle(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        app(ReportesController::class)->ContabilizaReporte(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, Filament::getTenant()->id);
        $user = User::where('id',Filament::auth()->id())->first();
        $importes = self::getCalcs();
        return [
            'team_id'=>Filament::getTenant()->id,
            'periodo'=>Filament::getTenant()->periodo,
            'ejercicio'=>Filament::getTenant()->ejercicio,
            'usuario'=>$user->name,
            'fecha'=>Carbon::now()->format('d/m/Y'),
            'mes_actual'=>app(MainChartsController::class)->mes_letras(Filament::getTenant()->periodo),
            'ventas'=>$importes['ventas'],
            'ventas_pa'=>$importes['ventas_pa'],
            'ventas_dif'=>$importes['ventas_dif'],
            'ventas_anuales'=>$importes['ventas_anuales'],
            'cobrar_importe'=>$importes['cobrar_importe'],
            'cuentas_x_cobrar_top3'=>$importes['cuentas_x_cobrar_top3'],
            'importe_vencido'=>$importes['importe_vencido'],
            'pagar_importe'=>$importes['pagar_importe'],
            'cuentas_x_pagar_top3'=>$importes['cuentas_x_pagar_top3'],
            'utilidad_importe'=>$importes['utilidad_importe'],
            'utilidad_ejercicio'=>$importes['utilidad_ejercicio'],
            'impuesto_estimado'=>$importes['impuesto_estimado'],
            'importe_inventario'=>$importes['importe_inventario'],
            'impuesto_mensual'=>$importes['impuesto_mensual'],
            'importe_iva'=>$importes['importe_iva'],
            'importe_ret'=>$importes['importe_ret'],
            'mes_ventas_data'=>$importes['mes_ventas_data'],
            'anio_ventas_data'=>$importes['anio_ventas_data'],
        ];
    }

    public static function getCalcs() :array
    {
        $team_id = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $periodo_ant = Filament::getTenant()->periodo - 1;
        $ejercicio = Filament::getTenant()->ejercicio;
        $datos = SaldosReportes::where('team_id',Filament::getTenant()->id)->where('codigo','40100000')->first();
        $importe = ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        $importe_a = ($datos?->anterior ?? 0) + ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $ventas = floatval($importe);
        $ventas_anuales = floatval($importe_a);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $ventas_pa = floatval(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo_ant,$ejercicio));
        //------------------------------------------------------------------------------------------------------------------------------------------
        //$cobrar_importe = app(MainChartsController::class)->GetCobrar($team_id);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $ventas_dif = $ventas-$ventas_pa;
        //------------------------------------------------------------------------------------------------------------------------------------------
        $cuentas_x_cobrar = EstadCXC_F::all();
        $cobrar_importe = $cuentas_x_cobrar->sum('saldo');
        $cuentas_x_cobrar_top3 = EstadCXC_F::orderBy('saldo','desc')->take(3)->get();
        //dd($cuentas_x_cobrar);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $fac_data = EstadCXC::select('factura','cliente')->distinct()->get();
        $facturas_vencidas = [];
        foreach ($fac_data as $fac)
        {
            $fecha = Carbon::create(EstadCXC::where('factura',$fac->factura)->first()->fecha)->format('Y-m-d');
            $corte = Carbon::create($ejercicio,$periodo,1)->format('Y-m-d');
            $vencimiento = Carbon::create($fecha)->addDays(30)->format('Y-m-d');
            $factura_i = EstadCXC::where('factura',$fac->factura)->get();
            if($factura_i->sum('cargos')-$factura_i->sum('abonos') > 0&&$vencimiento <= $corte) {
                $facturas_vencidas[] = [
                    'cliente' => $fac->cliente,
                    'factura' => $fac->factura,
                    'fecha' => $fecha,
                    'vencimiento' => $vencimiento,
                    'importe' => $factura_i->sum('cargos'),
                    'pagos' => $factura_i->sum('abonos'),
                    'saldo' => $factura_i->sum('cargos') - $factura_i->sum('abonos')
                ];
            }
        }
        //dd($facturas_vencidas);
        $vencido_c = array_column($facturas_vencidas, 'saldo');
        $vencido = array_sum($vencido_c);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $cuentas_x_pagar = EstadCXP_F::all();
        $pagar_importe = $cuentas_x_pagar->sum('saldo');
        $cuentas_x_pagar_top3 = EstadCXP_F::orderBy('saldo','desc')->take(3)->get();
        //------------------------------------------------------------------------------------------------------------------------------------------
        $utilidad_importe = app(MainChartsController::class)->GetUtiPer($team_id);
        $utilidad_ejercicio = app(MainChartsController::class)->GetUtiPerEjer($team_id);
        $impuesto_estimado = floatval($utilidad_ejercicio) * 0.30;
        //------------------------------------------------------------------------------------------------------------------------------------------
        $inven_data = Inventario::where('team_id',$team_id)->get();
        $importe_inventario = floatval($inven_data->sum('p_costo')) * floatval($inven_data->sum('exist'));
        //------------------------------------------------------------------------------------------------------------------------------------------
        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        $coef = floatval($fiscales?->coeficiente ?? 0);
        $impuesto_mensual = $utilidad_ejercicio * $coef * 0.3;
        //------------------------------------------------------------------------------------------------------------------------------------------
        $datos_iva_pag = SaldosReportes::where('team_id',Filament::getTenant()->id)->where('codigo','11801000')->first();
        $datos_iva_cob = SaldosReportes::where('team_id',Filament::getTenant()->id)->where('codigo','20801000')->first();
        $importe_iva_pag = ($datos_iva_pag?->cargos ?? 0) - ($datos_iva_pag?->abonos ?? 0);
        $importe_iva_cob = ($datos_iva_cob?->abonos ?? 0) - ($datos_iva_cob?->cargos ?? 0);
        $importe_iva = floatval($importe_iva_cob) - floatval($importe_iva_pag);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $datos_ret = SaldosReportes::where('team_id',Filament::getTenant()->id)
        ->whereIn('codigo',['21601000','21602000','21603000','21604000','21605000'])->get();
        $importe_ret = floatval($datos_ret->sum('abonos') ?? 0) - floatval($datos_ret->sum('cargos') ?? 0);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $mes_ventas_data = Auxiliares::where('team_id',$team_id)
            ->where('codigo','like','401%')
            ->where('a_periodo',$periodo)->where('a_ejercicio',$ejercicio)
            ->selectRaw('sum(abono) as importe,concepto')
            ->groupBy('concepto')
            ->orderBy('importe','desc')
            ->take(3)
            ->get();
        //------------------------------------------------------------------------------------------------------------------------------------------
        $ejercicio_ventas_data = Auxiliares::where('team_id',$team_id)
            ->where('codigo','like','401%')
            ->where('a_periodo','<=',$periodo)->where('a_ejercicio',$ejercicio)
            ->selectRaw('sum(abono) as importe,concepto')
            ->groupBy('concepto')
            ->orderBy('importe','desc')
            ->take(3)
            ->get();
        //------------------------------------------------------------------------------------------------------------------------------------------
        return ['ventas'=>$ventas,'ventas_pa'=>$ventas_pa,
            'ventas_dif'=>$ventas_dif,'ventas_anuales'=>$ventas_anuales,
            'cobrar_importe'=>$cobrar_importe,'cuentas_x_cobrar_top3'=>$cuentas_x_cobrar_top3,
            'importe_vencido'=>$vencido,'facturas_vencidas'=>$facturas_vencidas,'pagar_importe'=>$pagar_importe,
            'cuentas_x_pagar_top3'=>$cuentas_x_pagar_top3,'utilidad_importe'=>$utilidad_importe,'utilidad_ejercicio'=>$utilidad_ejercicio,
            'impuesto_estimado'=>$impuesto_estimado,'importe_inventario'=>$importe_inventario,
            'impuesto_mensual'=>$impuesto_mensual,'importe_iva'=>$importe_iva,'importe_ret'=>$importe_ret,
            'mes_ventas_data'=>$mes_ventas_data,'anio_ventas_data'=>$ejercicio_ventas_data,

        ];
    }

}
