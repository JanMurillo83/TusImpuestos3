<?php
$mes_act = intval(\Filament\Facades\Filament::getTenant()->periodo);
$eje_act = intval(\Filament\Facades\Filament::getTenant()->ejercicio);
$mes_let = '';
switch ($mes_act){
    case 1: $mes_let = 'Enero'; break;
    case 2: $mes_let = 'Febrero'; break;
    case 3: $mes_let = 'Marzo'; break;
    case 4: $mes_let = 'Abril'; break;
    case 5: $mes_let = 'Mayo'; break;
    case 6: $mes_let = 'Junio'; break;
    case 7: $mes_let = 'Julio'; break;
    case 8: $mes_let = 'Agosto'; break;
    case 9: $mes_let = 'Septiembre'; break;
    case 10: $mes_let = 'Octubre'; break;
    case 11: $mes_let = 'Noviembre'; break;
    case 12: $mes_let = 'Diciembre'; break;
}
$team_id = \Filament\Facades\Filament::getTenant()->id;
$ventas_final = \App\Models\SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first();
$anterior_vf = floatval($ventas_final?->anterior ?? 0);
$abonos_vf = floatval($ventas_final?->abonos ?? 0);
$cargos_vf = floatval($ventas_final?->cargos ?? 0);
$ventas_saldo = $abonos_vf-$cargos_vf;
$ventas_saldo_anual = $anterior_vf+$abonos_vf-$cargos_vf;
//-------------------------------------------------------
$clientes_final_c = \App\Models\CuentasCobrarTable::where('team_id',$team_id)
    ->where('periodo',$mes_act)
    ->where('ejercicio',$eje_act)
    ->where('tipo','C')
    ->sum('importe');
$clientes_final_a = \App\Models\CuentasCobrarTable::where('team_id',$team_id)
    ->where('periodo',$mes_act)
    ->where('ejercicio',$eje_act)
    ->where('tipo','A')
    ->sum('importe');
$cargos_cf = floatval($clientes_final_c ?? 0);
$abonos_cf = floatval($clientes_final_a ?? 0);
$clientes_saldo = $cargos_cf-$abonos_cf;
//-------------------------------------------------------
$proveedores_final_c = \App\Models\CuentasPagarTable::where('team_id',$team_id)
    ->where('periodo',$mes_act)
    ->where('ejercicio',$eje_act)
    ->where('tipo','C')
    ->sum('importe');
$proveedores_final_a = \App\Models\CuentasPagarTable::where('team_id',$team_id)
    ->where('periodo',$mes_act)
    ->where('ejercicio',$eje_act)
    ->where('tipo','A')
    ->sum('importe');
$cargos_pf = floatval($proveedores_final_c ?? 0);
$abonos_pf = floatval($proveedores_final_a ?? 0);
$proveedores_saldo = $cargos_pf-$abonos_pf;
//-------------------------------------------------------
$cuentas = DB::select("SELECT * FROM saldos_reportes WHERE nivel = 1 AND team_id = $team_id AND (COALESCE(anterior,0)+COALESCE(cargos,0)+COALESCE(abonos,0)) != 0 ");
$saldo_v = 0;
$saldo_g = 0;
$saldo_v_a = 0;
$saldo_g_a = 0;
$gastos = 0;
$gastos_a = 0;
foreach ($cuentas as $cuenta) {
    $cod = intval(substr($cuenta->codigo,0,3));
    if($cod > 399&&$cod < 500)
    {
        if($cuenta->naturaleza == 'D') {
            $saldo_v += ($cuenta->cargos - $cuenta->abonos);
            $saldo_v_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
        }else{
            $saldo_v += ($cuenta->abonos - $cuenta->cargos);
            $saldo_v_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
        }
    }
    if($cod > 500)
    {
        if($cuenta->naturaleza == 'D') {
            $saldo_g += ($cuenta->cargos - $cuenta->abonos);
            $saldo_g_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
        }else{
            $saldo_g += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
            $saldo_g_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
        }
    }
    if($cod > 599&&$cod < 699)
    {
        if($cuenta->naturaleza == 'D') {
            $gastos += ($cuenta->cargos - $cuenta->abonos);
            $gastos_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
        }else{
            $gastos += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
            $gastos_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
        }
    }
}
$gastos_a = floatval($gastos_a);
$utilidad = floatval($saldo_v) - floatval($saldo_g);
$utilidad_a = floatval($saldo_v_a) - floatval($saldo_g_a);
//-----------------------------------------------------------------------------------
$retenciones_final = \App\Models\SaldosReportes::where('team_id',$team_id)->where('codigo','21600000')->first();
$anterior_rf = floatval($retenciones_final?->anterior ?? 0);
$abonos_rf = floatval($retenciones_final?->abonos ?? 0);
$cargos_rf = floatval($retenciones_final?->cargos ?? 0);
$retenciones_saldo = $anterior_rf+$abonos_rf-$cargos_rf;
//-----------------------------------------------------------------------------------
$provisionales_final = \App\Models\SaldosReportes::where('team_id',$team_id)->where('codigo','21600000')->first();
$anterior_of = floatval($provisionales_final?->anterior ?? 0);
$abonos_of = floatval($provisionales_final?->abonos ?? 0);
$cargos_of = floatval($provisionales_final?->cargos ?? 0);
$provisionales_saldo = $anterior_of+$abonos_of-$cargos_of;
//---------------------------------------------------------------------
$fiscales = \App\Models\DatosFiscales::where('team_id',$team_id)->first();
$porc = floatval($fiscales?->porcentaje ?? 30) * 0.01;
$pago_isr_anual = $utilidad_a * $porc;
//---------------------------------------------------------------------
$ventas_x_cliente_mes = \App\Models\Auxiliares::where('team_id',$team_id)
    ->where('a_ejercicio',$eje_act)
    ->where('a_periodo',$mes_act)
    ->where('codigo','40101000')
    ->groupBy(['concepto'])
    ->selectRaw('sum(abono) as abonos, concepto')->orderBy('abonos','desc')
    ->get();
$monto_total_vm = 0;
$monto_top_m = 0;
$contador_vm = 0;
$data_clientes = [];
foreach ($ventas_x_cliente_mes as $vcm)
{
    if($contador_vm < 3){
        $monto_top_m+= floatval($vcm->abonos);
        $data_clientes[] = ['cuenta'=>$vcm->concepto,'importe'=>floatval($vcm->abonos)];
    }
    $monto_total_vm+= floatval($vcm->abonos);
    $contador_vm++;
}
$data_clientes[] = ['cuenta'=>'Otros Clientes','importe'=>($monto_total_vm-$monto_top_m)];
//------------------------------------------------------------------------------
$ventas_x_cliente_ani = \App\Models\Auxiliares::where('team_id',$team_id)
    ->where('a_ejercicio',$eje_act)
    ->where('a_periodo','<=',$mes_act)
    ->where('codigo','40101000')
    ->groupBy(['concepto'])
    ->selectRaw('sum(abono) as abonos, concepto')->orderBy('abonos','desc')
    ->get();
$monto_total_va = 0;
$monto_top_a = 0;
$contador_va = 0;
$data_clientes_a = [];
foreach ($ventas_x_cliente_ani as $vca)
{
    if($contador_va < 3){
        $monto_top_a+= floatval($vca->abonos);
        $data_clientes_a[] = ['cuenta'=>$vca->concepto,'importe'=>floatval($vca->abonos)];
    }
    $monto_total_va+= floatval($vca->abonos);
    $contador_va++;
}
$data_clientes_a[] = ['cuenta'=>'Otros Clientes','importe'=>($monto_total_va-$monto_top_a)];
//-----------------------------------------------------------------------------------------
$data_ctascobrar = [];
$cartera = \App\Models\CuentasCobrarTable::where('team_id',$team_id)
    ->where('periodo',$mes_act)
    ->where('ejercicio',$eje_act)
    ->groupBy(['cliente'])
    ->selectRaw('sum(importe) as saldo, cliente')->orderBy('saldo','desc')
    ->get();
$total_ctascobrar = 0;
$top_cartera = 0;
$count_carte = 0;
foreach ($cartera as $carte){
    if($count_carte < 3){
        $top_cartera+= floatval($carte->saldo);
        $data_ctascobrar[] = ['cuenta'=>$carte->cliente,'importe'=>floatval($carte->saldo)];
    }
    $total_ctascobrar+= floatval($carte->saldo);
    $count_carte++;
}

$data_ctascobrar[]= ['cuenta'=>'Otros Clientes','importe'=>($total_ctascobrar-$top_cartera)];
$monto_cartera_tot = $total_ctascobrar-$top_cartera;
//-------------------------------------------------------------
$data_ctascobrar_a = [];
$cartera_a = \App\Models\CuentasCobrarTable::where('team_id',$team_id)
    ->where(DB::raw("EXTRACT(MONTH FROM vencimiento)"),'>=',$mes_act)
    ->groupBy(['cliente'])
    ->selectRaw('sum(saldo) as saldo, cliente')->orderBy('saldo','desc')
    ->get();
$total_ctascobrar_a = 0;
$top_cartera_a = 0;
$count_carte_a = 0;
foreach ($cartera_a as $carte) {
    if ($count_carte_a < 3) {
        $top_cartera_a += floatval($carte->saldo);
        $data_ctascobrar_a[] = ['cuenta' => $carte->cliente, 'importe' => floatval($carte->saldo)];
    }
    $total_ctascobrar_a += floatval($carte->saldo);
    $count_carte_a++;
}
$data_ctascobrar_a[]= ['cuenta'=>'Otros Clientes','importe'=>($total_ctascobrar-$top_cartera)];
$monto_cartera_tot_a = $total_ctascobrar_a-$top_cartera_a;
//--------------------------------------------------------------------
    $data_ctaspagar = [];
    $ctaspagar = \App\Models\CuentasPagarTable::where('team_id',$team_id)
        ->where('periodo',$mes_act)
        ->where('ejercicio',$eje_act)
        ->groupBy(['cliente'])
        ->selectRaw('sum(importe) as saldo, cliente')->orderBy('saldo','desc')
        ->get();
    $total_ctaspagar = 0;
    $top_pagar = 0;
    $count_pagar = 0;
    foreach ($ctaspagar as $carte){
        if($count_pagar < 3){
            $top_pagar+= floatval($carte->saldo);
            $data_ctaspagar[] = ['cuenta'=>$carte->cliente,'importe'=>floatval($carte->saldo)];
        }
        $total_ctaspagar+= floatval($carte->saldo);
        $count_pagar++;
    }

    $data_ctaspagar[]= ['cuenta'=>'Otros Proveedores','importe'=>($total_ctaspagar-$top_pagar)];
    $monto_pagar_tot = $total_ctaspagar-$top_pagar;
    //dd($data_ctaspagar);
//------------------------------------------------------
    $data_ctaspagar_a = [];
    $ctaspagar_a = \App\Models\CuentasPagarTable::where('team_id',$team_id)
        ->where(DB::raw("EXTRACT(MONTH FROM vencimiento)"),'>=',$mes_act)
        ->groupBy(['cliente'])
        ->selectRaw('sum(importe) as saldo, cliente')->orderBy('saldo','desc')
        ->get();
    $total_ctaspagar_a = 0;
    $top_pagar_a = 0;
    $count_pagar_a = 0;
    foreach ($ctaspagar_a as $carte){
        if($count_pagar_a < 3){
            $top_pagar_a+= floatval($carte->saldo);
            $data_ctaspagar_a[] = ['cuenta'=>$carte->cliente,'importe'=>floatval($carte->saldo)];
        }
        $total_ctaspagar_a+= floatval($carte->saldo);
        $count_pagar_a++;
    }
    $data_ctaspagar_a[]= ['cuenta'=>'Otros Proveedores','importe'=>($total_ctaspagar_a-$top_pagar_a)];
    $monto_pagar_tot_a = $total_ctaspagar_a-$top_pagar_a;
    //------------------------------------------------------------------------
    $inventarios = [];
    $inventarios[] = ['cuenta'=>'Producto  Terminado','importe'=>0];
    $inventarios[] = ['cuenta'=>'Materia Prima','importe'=>0];
    $inventarios[] = ['cuenta'=>'Inventario en Proceso','importe'=>0];
    $inventario_total = 0;
//------------------------------------------------------------------------
$rets = \App\Models\SaldosReportes::where('team_id',$team_id)->where('codigo','21600000')->first();
$iva_i = \App\Models\SaldosReportes::where('team_id',$team_id)
    ->whereIn('codigo',['11801000','20801000'])->first();
$isr_i = \App\Models\SaldosReportes::where('team_id',$team_id)->where('codigo','11400000')->first();
$retencion_f = $rets?->abonos ?? 0 -$rets?->cargos ?? 0;
$iva_f = $iva_i?->cargos ?? 0 - $iva_i?->abonos ?? 0;
$isr_f = $isr_i?->cargos ?? 0 - $isr_i?->abonos ?? 0;
$impuestos = [];
$impuestos[] = ['cuenta'=>'ISR Propio','importe'=>floatval($isr_f)];
$impuestos[] = ['cuenta'=>'IVA','importe'=>floatval($iva_f)];
$impuestos[] = ['cuenta'=>'Retenciones','importe'=>floatval($retencion_f)];
$impuestos_total = $isr_f+$iva_f+$retencion_f;

?>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/themes/light.css" />
<script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/shoelace-autoloader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<x-filament-panels::page>
    <style>
        .card-basic {
            --border-radius: 2rem;
            width: 20rem !important;
            margin-left: 2rem !important;
        }
        .card-basic2 {
            --border-radius: 2rem;
            width: 30rem !important;
            margin-left: 1rem !important;
        }
        .label-title{
            color: var(--sl-color-neutral-400) !important;
            font-size: 1rem !important;
        }
        .label-number{
            color: var(--sl-color-success-400) !important;
            font-size: 2rem !important;
        }
        .label-number2{
            color: var(--sl-color-warning-400) !important;
            font-size: 2rem !important;
        }
        .label-footer{
            color: var(--sl-color-primary-400) !important;
            font-size: 1rem !important;
        }
        .label-footer2{
            color: var(--sl-color-warning-400) !important;
            font-size: 1rem !important;
        }
        .label-footer3{
            color: var(--sl-color-danger-400) !important;
            font-size: 1rem !important;
        }
        .button1{
            margin-left: 1rem !important;
            width: 20rem !important;
        }
        .container-card {
            display: grid;
            grid-template-columns: auto auto;
            padding: 10px;
        }
    </style>

    <script>
        $(document).ready(function() {
            $("#wait").hide();
            $("#tarjeta1").hide();
            $("#tarjeta2").hide();
            $("#tarjeta3").hide();
            $("#tarjeta4").hide();
            $("#tarjeta5").hide();
            $("#tarjeta6").hide();
            $("#tarjeta7").hide();
            $("#tarjeta8").hide();
        });
        async function getOutput() {
            console.log('init');
            $("#main").hide();
            $("#wait").show();
            await $.ajax({
                url:'contabilizar',
                data: {
                    team_id: $('#team_id').val(),
                    periodo: $('#periodo').val(),
                    ejercicio: $('#ejercicio').val(),
                },
                complete: function (response) {
                    $('#output').html(response.responseText);
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#main").show();
            $("#wait").hide();
            window.location.reload();
            return false;
        }
        async function tarjeta1() {
            $("#main").hide();
            $("#tarjeta1").show();
            var chart1;
            await $.ajax({
                url:'grafica1',
                data: {
                    datos: <?php echo json_encode($data_clientes); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica1_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta2() {
            $("#main").hide();
            $("#tarjeta2").show();
            var chart1;
            await $.ajax({
                url:'grafica2',
                data: {
                    datos: <?php echo json_encode($data_clientes_a); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica2_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta3() {
            $("#main").hide();
            $("#tarjeta3").show();
            var chart1;
            await $.ajax({
                url:'grafica3',
                data: {
                    datos: <?php echo json_encode($data_ctascobrar); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica3_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta4() {
            $("#main").hide();
            $("#tarjeta4").show();
            var chart1;
            await $.ajax({
                url:'grafica4',
                data: {
                    datos: <?php echo json_encode($data_ctascobrar_a); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica4_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta5() {
            $("#main").hide();
            $("#tarjeta5").show();
            var chart1;
            await $.ajax({
                url:'grafica5',
                data: {
                    datos: <?php echo json_encode($data_ctaspagar); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica5_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta6() {
            $("#main").hide();
            $("#tarjeta6").show();
            var chart1;
            await $.ajax({
                url:'grafica6',
                data: {
                    datos: <?php echo json_encode($data_ctaspagar_a); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica6_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta7() {
            $("#main").hide();
            $("#tarjeta7").show();
            var chart1;
            await $.ajax({
                url:'grafica7',
                data: {
                    datos: <?php echo json_encode($inventarios); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica7_img").attr("src",chart1);
            console.log(chart1);
        }
        async function tarjeta8() {
            $("#main").hide();
            $("#tarjeta8").show();
            var chart1;
            await $.ajax({
                url:'grafica8',
                data: {
                    datos: <?php echo json_encode($impuestos); ?>,
                },
                complete: function (response) {
                    chart1 = response.responseText;
                },
                error: function () {
                    $('#output').html('Bummer: there was an error!');
                }
            });
            $("#grafica8_img").attr("src",chart1);
            console.log(chart1);
        }
        function cierra_tarjetas(){
            $("#tarjeta1").hide();
            $("#tarjeta2").hide();
            $("#tarjeta3").hide();
            $("#tarjeta4").hide();
            $("#tarjeta5").hide();
            $("#tarjeta6").hide();
            $("#tarjeta7").hide();
            $("#tarjeta8").hide();
            $("#main").show();
        }
    </script>
    <div class="container" id="main">
        <div class="row" style="display: none" id="output">
            <input type="hidden" id="team_id" name="team_id" value="{{ $team_id }}">
            <input type="hidden" id="periodo" name="periodo" value="{{ $mes_act }}">
            <input type="hidden" id="ejercicio" name="ejercicio" value="{{ $eje_act }}">
        </div>
        <div class="row" >
            <div class="col-4">
                <label style="font-size: 1rem; font-weight: bold">Razón Social: <label style="color: #9f1239;margin-left: 1rem">{{$fiscales?->nombre ?? ''}}</label> <label style="margin-left: 2rem">Periodo:</label><label style="color: #9f1239; margin-left: 1rem">{{$mes_let}}</label> <label style="margin-left: 2rem">Ejercicio:</label> <label style="color: #9f1239; margin-left: 1rem">{{$eje_act}}</label><sl-button variant="neutral" size="small" pill style="margin-left: 30rem" onclick="getOutput()">Actualizar Datos</sl-button></label>
            </div>
        </div>
        <div class="row">
            <label style="font-size: 2rem; font-weight: bold">Resumen general del negocio</label>
        </div>
        <div class="row" style="margin-left: 7rem; margin-top: 1rem">
            <sl-card class="card-basic">
                <div><label class="label-title">Ventas del Mes</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$ventas_saldo}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer">Periodo {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
            <sl-card class="card-basic">
                <div><label class="label-title">Ventas del Año</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$ventas_saldo_anual}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer">Enero - {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
            <sl-card class="card-basic">
                <div><label class="label-title">Cuentas por Cobrar</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$clientes_saldo}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer2">Periodo {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
            <sl-card class="card-basic">
                <div><label class="label-title">Cuentas por Pagar</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$proveedores_saldo}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer3">Periodo {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
        </div>
        <div class="row" style="margin-left: 6rem; margin-top: 1rem">
            <sl-card class="card-basic2">
                <div><label class="label-title">Utilidad del Periodo</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$utilidad}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer2">Periodo {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
            <sl-card class="card-basic2">
                <div><label class="label-title">Utilidad acumulada del Ejercicio</label></div>
                <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$utilidad_a}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer3">Enero - {{$mes_let}} {{$eje_act}}</label></div>
            </sl-card>
            <sl-card class="card-basic2">
                <div><label class="label-title">Impuesto anual estimado (30%)</label></div>
                <div><label class="label-number2"><sl-format-number type="currency" currency="USD" value="{{$pago_isr_anual}}" lang="en-US"></sl-format-number></label></div>
                <div><label class="label-footer">Calculado sobre la utilidad acumulada del ejercicio</label></div>
            </sl-card>
        </div>
        <div class="row" style="margin-left: 9rem; margin-top: 2rem">
            <sl-button class="button1" variant="warning" pill outline onclick="tarjeta1()">Ventas del mes por Cliente</sl-button>
            <sl-button class="button1" variant="warning" pill outline onclick="tarjeta2()">Ventas del año - Mejores Clientes</sl-button>
            <sl-button class="button1" variant="warning" pill outline onclick="tarjeta3()">Cuentas por Cobrar</sl-button>
            <sl-button class="button1" variant="warning" pill outline onclick="tarjeta4()">Cartera Vencida - Clientes</sl-button>
        </div>
        <div class="row" style="margin-left: 9rem; margin-top: 1rem">
            <sl-button class="button1" variant="neutral" pill outline onclick="tarjeta5()">Cuentas por Pagar</sl-button>
            <sl-button class="button1" variant="neutral" pill outline onclick="tarjeta6()">Cartera vencida - Proveedores</sl-button>
            <sl-button class="button1" variant="neutral" pill outline onclick="tarjeta7()">Inventario</sl-button>
            <sl-button class="button1" variant="neutral" pill outline onclick="tarjeta8()">Impuestos del Mes</sl-button>
        </div>
    </div>
    <div class="container" id="wait">
        <center>
            <img style="margin-top: -4rem; margin-left: -5rem; background-color: transparent !important" src="{{asset('/images/transparent-loading.gif')}}" alt="Loading..." width="300">
        </center>
    </div>
    <div class="container" id="tarjeta1" style="width: 100% !important; margin-top: 2rem">
        <center>
        <div class="row">
            <sl-card width="70rem">
                <div slot="header">
                    <label>Ventas del mes por cliente<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                </div>
                <div class="card-body">
                    <div class="container-card">
                        <div>
                            <img id="grafica1_img" alt="grafica1" src="">
                        </div>
                        <div>
                            <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                </thead>
                                <?php
                                    $porc1 = floatval($data_clientes[0]['importe'])*100/floatval($monto_total_vm);
                                    $porc2 = floatval($data_clientes[1]['importe'])*100/floatval($monto_total_vm);
                                    $porc3 = floatval($data_clientes[2]['importe'])*100/floatval($monto_total_vm);
                                    $porc4 = floatval($data_clientes[3]['importe'])*100/floatval($monto_total_vm);
                                ?>
                                <tbody>
                                    <tr>
                                        <td>{{$data_clientes[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes[3]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes[3]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc4,2).'%'}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </sl-card>
        </div>
        </center>
    </div>
    <div class="container" id="tarjeta2" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Ventas del año - mejores clientes<label style="margin-left: 5rem">Ejercicio: {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica2_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                    </thead>
                                    <?php
                                    $porc1 = floatval($data_clientes_a[0]['importe'])*100/floatval($monto_total_va);
                                    $porc2 = floatval($data_clientes_a[1]['importe'])*100/floatval($monto_total_va);
                                    $porc3 = floatval($data_clientes_a[2]['importe'])*100/floatval($monto_total_va);
                                    $porc4 = floatval($data_clientes_a[3]['importe'])*100/floatval($monto_total_va);
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>{{$data_clientes_a[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes_a[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes_a[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes_a[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes_a[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes_a[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_clientes_a[3]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_clientes_a[3]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc4,2).'%'}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta3" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Cuentas por Cobrar<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica3_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Saldo</th>
                                        <th>% cartera</th>
                                    </tr>
                                    </thead>
                                    <?php
                                    $porc1 = floatval($data_ctascobrar[0]['importe'])*100/floatval($total_ctascobrar);
                                    $porc2 = floatval($data_ctascobrar[1]['importe'])*100/floatval($total_ctascobrar);
                                    $porc3 = floatval($data_ctascobrar[2]['importe'])*100/floatval($total_ctascobrar);
                                    $porc4 = floatval($data_ctascobrar[3]['importe'])*100/floatval($total_ctascobrar);
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>{{$data_ctascobrar[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar[3]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar[3]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc4,2).'%'}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta4" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Cartera Vencida<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica4_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Saldo</th>
                                        <th>% cartera</th>
                                    </tr>
                                    </thead>
                                    <?php
                                    $porc1 = floatval($data_ctascobrar_a[0]['importe'])*100/floatval($total_ctascobrar_a);
                                    $porc2 = floatval($data_ctascobrar_a[1]['importe'])*100/floatval($total_ctascobrar_a);
                                    $porc3 = floatval($data_ctascobrar_a[2]['importe'])*100/floatval($total_ctascobrar_a);
                                    $porc4 = floatval($data_ctascobrar_a[3]['importe'])*100/floatval($total_ctascobrar_a);
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>{{$data_ctascobrar_a[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar_a[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar_a[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar_a[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar_a[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar_a[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$data_ctascobrar_a[3]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctascobrar_a[3]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc4,2).'%'}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta5" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Cuentas por Pagar<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica5_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Proveedor</th>
                                        <th>Saldo</th>
                                        <th>% cartera</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($data_ctaspagar as $data_ctas)
                                    <?php
                                        $porc1 = floatval($data_ctas['importe'])*100/floatval($total_ctaspagar);
                                    ?>
                                    <tr>
                                        <td>{{$data_ctas['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($data_ctas['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta6" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Cuentas por Pagar Vencidas<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica6_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Proveedor</th>
                                        <th>Saldo</th>
                                        <th>% cartera</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($data_ctaspagar_a as $data_ctas)
                                        <?php
                                        $porc1 = floatval($data_ctas['importe'])*100/floatval($total_ctaspagar_a);
                                        ?>
                                        <tr>
                                            <td>{{$data_ctas['cuenta']}}</td>
                                            <td style="text-align: right">{{'$'.number_format($data_ctas['importe'],2)}}</td>
                                            <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta7" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Inventarios<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica7_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Categoria</th>
                                        <th>Importe</th>
                                        <th>% del total</th>
                                    </tr>
                                    </thead>
                                    <?php
                                    //$porc1 = floatval($inventarios[0]['importe'])*100/floatval($inventario_total);
                                    //$porc2 = floatval($inventarios[1]['importe'])*100/floatval($inventario_total);
                                    //$porc3 = floatval($inventarios[2]['importe'])*100/floatval($inventario_total);
                                    $porc1 = 0;
                                    $porc2 = 0;
                                    $porc3 = 0;
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>{{$inventarios[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($inventarios[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$inventarios[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($inventarios[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$inventarios[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($inventarios[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
    <div class="container" id="tarjeta8" style="width: 100% !important; margin-top: 2rem">
        <center>
            <div class="row">
                <sl-card width="70rem">
                    <div slot="header">
                        <label>Impuestos del Mes<label style="margin-left: 5rem">Periodo: {{$mes_let}} - {{$eje_act}}</label><sl-button style="margin-left: 50rem" onclick="cierra_tarjetas()" variant="neutral" pill size="small">Regresar</sl-button></label>
                    </div>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <img id="grafica8_img" alt="grafica2" src="">
                            </div>
                            <div>
                                <table class="table table-striped" style="width: 40rem; margin-top: 8rem">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Impuesto</th>
                                        <th>Importe</th>
                                        <th>% del total</th>
                                    </tr>
                                    </thead>
                                    <?php
                                    $porc1 = floatval($impuestos[0]['importe'])*100/floatval($impuestos_total);
                                    $porc2 = floatval($impuestos[1]['importe'])*100/floatval($impuestos_total);
                                    $porc3 = floatval($impuestos[2]['importe'])*100/floatval($impuestos_total);
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>{{$impuestos[0]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($impuestos[0]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$impuestos[1]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($impuestos[1]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc2,2).'%'}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{$impuestos[2]['cuenta']}}</td>
                                        <td style="text-align: right">{{'$'.number_format($impuestos[2]['importe'],2)}}</td>
                                        <td style="text-align: right">{{number_format($porc3,2).'%'}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </center>
    </div>
</x-filament-panels::page>
