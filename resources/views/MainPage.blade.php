<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TusImpuestos</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/shoelace-autoloader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/themes/light.css">
    <?php
        use Filament\Facades\Filament;
        use App\Http\Controllers\MainChartsController;
        $team = DB::table('teams')->where('id',$team_id)->first();
        $cuenta = '40100000';
        $mes_act = $team->periodo;
        $eje_act = $team->ejercicio;
        $mes_let = app(MainChartsController::class)->mes_letras($mes_act);
        $fiscales = \App\Models\DatosFiscales::where('team_id',$team_id)->first();
        $porc = floatval($fiscales?->porcentaje ?? 30) * 0.01;
        $ventas_saldo = app(MainChartsController::class)->GeneraAbonos($team_id,$cuenta,$mes_act,$eje_act);
        $ventas_saldo_an = app(MainChartsController::class)->GeneraAbonos_an($team_id,$cuenta,$mes_act,$eje_act);
        $aux_ventas_mes = app(MainChartsController::class)->GeneraAbonos_Aux($team_id,$cuenta,$mes_act,$eje_act);
        $aux_ventas_anio = app(MainChartsController::class)->GeneraAbonos_Aux_an($team_id,$cuenta,$mes_act,$eje_act);
        //-----------------------------------------------------------------------------------------------------------
        $cuenta_c = '10500000';
        $ctas_cobrar = app(MainChartsController::class)->CuentasCobrar($team_id,$cuenta_c,$mes_act,$eje_act);
        $tot_ctas_cobrar = $ctas_cobrar->sum('importe');
        //-----------------------------------------------------------------------------------------------------------
        $cuenta_p = '20100000';
        $ctas_pagar = app(MainChartsController::class)->CuentasPagar($team_id,$cuenta_p,$mes_act,$eje_act);
        $tot_ctas_pagar = $ctas_pagar->sum('importe');
        //-----------------------------------------------------------------------------------------------------------
        $ingresos = app(MainChartsController::class)->UtilidadPeriodo($team_id,$mes_act,$eje_act);
        $gastos = app(MainChartsController::class)->UtilidadPeriodoGastos($team_id,$mes_act,$eje_act);
        $utilidad = $ingresos->sum('importe') - ($gastos->sum('abono')+$gastos->sum('cargo'));
        //-----------------------------------------------------------------------------------------------------------
        $ingresos_a = app(MainChartsController::class)->UtilidadEjercicio($team_id,$mes_act,$eje_act);
        $gastos_a = app(MainChartsController::class)->UtilidadEjercicioGastos($team_id,$mes_act,$eje_act);
        $utilidad_ejericio = 0;
        //dd($gastos_a,$ingresos_a);
    ?>
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
        //let host = "{{env('APP_URL','http://localhost:8000')}}";
        let host = "https://app-tusimpuestos.com";
        function showDialog1() {
            const dialog1 = document.getElementById("dialog1");
            dialog1.show();
        }

        async function muestra_detalle1(param) {
            const dialog1 = document.getElementById("dialog1");
            const dialog2 = document.getElementById("dialog2");
            dialog2.addEventListener('sl-hide', () => {
                dialog2.hide();
                dialog1.show();
            })
            let data1;
            console.log(host);
            await $.ajax({
                url:`${host}/api/auxvencpto`,
                data: {
                    team_id:{{$team_id}},
                    cuenta:"40100000",
                    periodo:{{$mes_act}},
                    ejercicio:{{$eje_act}},
                    concepto:param
                },
                complete: function (response) {
                    data1 = response.responseText;
                },
                error: function (error) {
                    console.log(error);
                }
            });
            let datos = JSON.parse(data1);
            $("#dialog2").attr('label',`Ventas ${datos[0].concepto} {{$mes_let}} - {{$eje_act}}`);
            let imp_tot = 0;

            $("#table_1 tbody tr").remove();
            datos.forEach(element => {
                console.log(element);
                let importe = parseFloat(element.abono);
                let fec1 = element.fecha.substring(0,4);
                let fec2 = element.fecha.substring(5,7);
                let fec3 = element.fecha.substring(8,10);
                let fecha = fec3+'-'+fec2+'-'+fec1;
                imp_tot+=importe;
                let newRow =
                    `<tr>
                        <td>${element.concepto}</td>
                        <td>${element.tipo}${element.folio}</td>
                        <td>${element.factura}</td>
                        <td>${fecha}</td>
                        <td style='text-align: right'>{{'$'}}${importe.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>`;
                document.getElementById('table_1').
                getElementsByTagName('tbody')[0].
                insertAdjacentHTML('beforeend', newRow);
            });
            $("#total_table1").text('$'+imp_tot.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            dialog1.hide();
            dialog2.show();
        }

        function showDialog2() {
            const dialog1 = document.getElementById("dialog3");
            dialog1.show();
        }
        async function muestra_detalle2(param) {
            const dialog1 = document.getElementById("dialog3");
            const dialog2 = document.getElementById("dialog4");
            dialog2.addEventListener('sl-hide', () => {
                dialog2.hide();
                dialog1.show();
            })
            let data1;
            console.log(host);
            await $.ajax({
                url:`${host}/api/auxvencptoan`,
                data: {
                    team_id:{{$team_id}},
                    cuenta:"40100000",
                    periodo:{{$mes_act}},
                    ejercicio:{{$eje_act}},
                    concepto:param
                },
                complete: function (response) {
                    data1 = response.responseText;
                },
                error: function (error) {
                    console.log(error);
                }
            });
            let datos = JSON.parse(data1);
            $("#dialog4").attr('label',`Ventas ${datos[0].concepto} {{$mes_let}} - {{$eje_act}}`);
            let imp_tot = 0;

            $("#table_2 tbody tr").remove();
            datos.forEach(element => {
                console.log(element);
                let importe = parseFloat(element.abono);
                let fec1 = element.fecha.substring(0,4);
                let fec2 = element.fecha.substring(5,7);
                let fec3 = element.fecha.substring(8,10);
                let fecha = fec3+'-'+fec2+'-'+fec1;
                imp_tot+=importe;
                let newRow =
                    `<tr>
                        <td>${element.concepto}</td>
                        <td>${element.tipo}${element.folio}</td>
                        <td>${element.factura}</td>
                        <td>${fecha}</td>
                        <td style='text-align: right'>{{'$'}}${importe.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>`;
                document.getElementById('table_2').
                getElementsByTagName('tbody')[0].
                insertAdjacentHTML('beforeend', newRow);
            });
            $("#total_table2").text('$'+imp_tot.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            dialog1.hide();
            dialog2.show();
        }
        function showDialog3() {
            const dialog1 = document.getElementById("dialog5");
            dialog1.show();
        }
        async function muestra_detalle3(param) {
            const dialog1 = document.getElementById("dialog5");
            const dialog2 = document.getElementById("dialog6");
            dialog2.addEventListener('sl-hide', () => {
                dialog2.hide();
                dialog1.show();
            })
            let data1;
            console.log(host);
            await $.ajax({
                url:`${host}/api/ctascobrardet`,
                data: {
                    team_id:{{$team_id}},
                    cuenta:"10500000",
                    periodo:{{$mes_act}},
                    ejercicio:{{$eje_act}},
                    concepto:param
                },
                complete: function (response) {
                    data1 = response.responseText;
                },
                error: function (error) {
                    console.log(error);
                }
            });
            let datos = JSON.parse(data1);
            $("#dialog6").attr('label',`Cuentas por Cobrar ${datos[0].concepto} {{$mes_let}} - {{$eje_act}}`);
            let imp_tot = 0;
            let imp_tot_p = 0;

            $("#table_3 tbody tr").remove();
            datos.forEach(element => {
                console.log(element);
                let importe = parseFloat(element.cargo);
                let pago = parseFloat(element.abono);
                let fec1 = element.fecha.substring(0,4);
                let fec2 = element.fecha.substring(5,7);
                let fec3 = element.fecha.substring(8,10);
                let fecha = fec3+'-'+fec2+'-'+fec1;
                imp_tot+=importe;
                imp_tot_p+=pago;
                let newRow =
                    `<tr>
                        <td>${element.concepto}</td>
                        <td>${element.tipo}${element.folio}</td>
                        <td>${element.factura}</td>
                        <td>${fecha}</td>
                        <td style='text-align: right'>{{'$'}}${importe.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td style='text-align: right'>{{'$'}}${pago.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>`;
                document.getElementById('table_3').
                getElementsByTagName('tbody')[0].
                insertAdjacentHTML('beforeend', newRow);
            });
            $("#total_table3").text('$'+imp_tot.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_table3_p").text('$'+imp_tot_p.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            let saldo_t = imp_tot-imp_tot_p;
            $("#total_table3_p_t").text('$'+saldo_t.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            dialog1.hide();
            dialog2.show();
        }
        function showDialog4() {
            const dialog1 = document.getElementById("dialog7");
            dialog1.show();
        }
        async function muestra_detalle4(param) {
            const dialog1 = document.getElementById("dialog7");
            const dialog2 = document.getElementById("dialog8");
            dialog2.addEventListener('sl-hide', () => {
                dialog2.hide();
                dialog1.show();
            })
            let data1;
            console.log(host);
            await $.ajax({
                url:`${host}/api/ctaspagardet`,
                data: {
                    team_id:{{$team_id}},
                    cuenta:"20100000",
                    periodo:{{$mes_act}},
                    ejercicio:{{$eje_act}},
                    concepto:param
                },
                complete: function (response) {
                    data1 = response.responseText;
                },
                error: function (error) {
                    console.log(error);
                }
            });
            let datos = JSON.parse(data1);
            $("#dialog8").attr('label',`Cuentas por Pagar ${datos[0].concepto} {{$mes_let}} - {{$eje_act}}`);
            let imp_tot = 0;
            let imp_tot_p = 0;

            $("#table_4 tbody tr").remove();
            datos.forEach(element => {
                console.log(element);
                let importe = parseFloat(element.abono);
                let pago = parseFloat(element.cargo);
                let fec1 = element.fecha.substring(0,4);
                let fec2 = element.fecha.substring(5,7);
                let fec3 = element.fecha.substring(8,10);
                let fecha = fec3+'-'+fec2+'-'+fec1;
                imp_tot+=importe;
                imp_tot_p+=pago;
                let newRow =
                    `<tr>
                        <td>${element.concepto}</td>
                        <td>${element.tipo}${element.folio}</td>
                        <td>${element.factura}</td>
                        <td>${fecha}</td>
                        <td style='text-align: right'>{{'$'}}${importe.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td style='text-align: right'>{{'$'}}${pago.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>`;
                document.getElementById('table_4').
                getElementsByTagName('tbody')[0].
                insertAdjacentHTML('beforeend', newRow);
            });
            $("#total_table4").text('$'+imp_tot.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_table4_p").text('$'+imp_tot_p.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            let saldo_t = imp_tot-imp_tot_p;
            $("#total_table4_p_t").text('$'+saldo_t.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            dialog1.hide();
            dialog2.show();
        }
    </script>
</head>
    <body style="background-color: var(--sl-color-primary-50) !important;">
        <div class="container">
            <div class="row mt-2">
                <div class="col-12">
                    <table>
                        <tr>
                            <td style="font-size: 1rem; font-weight: bold">Razón Social:</td>
                            <td colspan="3" style="color: #9f1239;margin-left: 2rem">{{$fiscales?->nombre ?? ''}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 1rem; font-weight: bold">Periodo:</td>
                            <td style="color: #9f1239; margin-left: 1rem">{{$mes_let}}</td>
                            <td style="font-size: 1rem; font-weight: bold">Ejercicio:</td>
                            <td style="color: #9f1239; margin-left: 1rem">{{$eje_act}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-3">
                    <sl-card class="card-basic" onclick="showDialog1()">
                        <div><label class="label-title">Ventas del Mes</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$ventas_saldo}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer">Periodo {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
                <div class="col-3">
                    <sl-card class="card-basic" onclick="showDialog2()">
                        <div><label class="label-title">Ventas del Año</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$ventas_saldo_an}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer">Enero - {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
                <div class="col-3">
                    <sl-card class="card-basic" onclick="showDialog3()">
                        <div><label class="label-title">Cuentas por Cobrar</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$tot_ctas_cobrar}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer">Enero - {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
                <div class="col-3">
                    <sl-card class="card-basic" onclick="showDialog4()">
                        <div><label class="label-title">Cuentas por Pagar</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$tot_ctas_pagar}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer">Periodo {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-4">
                    <sl-card class="card-basic" onclick="showDialog4()">
                        <div><label class="label-title">Utilidad / Perdida del Periodo</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$utilidad}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer2">Periodo {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
                <div class="col-4">
                    <sl-card class="card-basic" onclick="showDialog4()">
                        <div><label class="label-title">Utilidad / Perdida del Ejercicio</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$utilidad_ejericio}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer2">Periodo Enero - {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
                <div class="col-4">
                    <sl-card class="card-basic" onclick="showDialog4()">
                        <div><label class="label-title">Impuestos del Periodo</label></div>
                        <div><label class="label-number"><sl-format-number type="currency" currency="USD" value="{{$tot_ctas_pagar}}" lang="en-US"></sl-format-number></label></div>
                        <div><label class="label-footer2">Periodo {{$mes_let}} {{$eje_act}}</label></div>
                    </sl-card>
                </div>
            </div>
        </div>
    <!------------------------Dialogos-------------------------------------->
        <sl-dialog label="Ventas del mes por cliente: Enero - {{$mes_let}} - {{$eje_act}}" id="dialog1" style="--width: 50rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $acum_1 = 0;?>
                                    @foreach($aux_ventas_mes as $data_client)
                                            <?php
                                            $porc1 = floatval($data_client->abono)*100/max(floatval($ventas_saldo),1);
                                            $acum_1+=floatval($data_client->abono);
                                            ?>
                                        <tr>
                                            <td><a href="#" onclick="muestra_detalle1('{{$data_client->concepto}}')">{{$data_client->concepto}}</a></td>
                                            <td style="text-align: right">{{'$'.number_format($data_client->abono,2)}}</td>
                                            <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                        <td style="font-weight: bold">TOTAL :</td>
                                        <td style="text-align: right;font-weight: bold">{{'$'.number_format($acum_1,2)}}</td>
                                        <td></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!----------------------Detalle 1------------------------------>
        <sl-dialog label="" id="dialog2" style="--width: 50rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" id="table_1">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Póliza</th>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Importe</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                        <td  colspan="4" style="font-weight: bold">TOTAL :</td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table1"></label></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!----------------------------Dialogo 2----------------------------------------------------->
        <sl-dialog label="Ventas Acumuladas: Enero - {{$mes_let}} - {{$eje_act}}" id="dialog3" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $acum_1 = 0;?>
                                    @foreach($aux_ventas_anio as $data_client)
                                            <?php
                                            $porc1 = floatval($data_client->abono)*100/max(floatval($ventas_saldo_an),1);
                                            $acum_1+=floatval($data_client->abono);
                                            $impo = floatval($data_client->abono);
                                            ?>
                                        <tr>
                                            <td><a href="#" onclick="muestra_detalle2('{{$data_client->concepto}}')">{{$data_client->concepto}}</a></td>
                                            <td style="text-align: right">{{'$'.number_format($impo,2)}}</td>
                                            <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <td style="font-weight: bold">TOTAL :</td>
                                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($acum_1,2)}}</td>
                                    <td></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!-------------------------Detalle 2--------------------------------->
        <sl-dialog label="" id="dialog4" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" id="table_2" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Póliza</th>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Importe</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                    <td  colspan="4" style="font-weight: bold">TOTAL :</td>
                                    <td style="text-align: right;font-weight: bold"><label id="total_table2"></label></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!----------------------------------Cuentas_x_Cobrar-------------------------------->
        <sl-dialog label="Cuentas por Cobrar: Enero - {{$mes_let}} - {{$eje_act}}" id="dialog5" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $acum_1 = 0;?>
                                    @foreach($ctas_cobrar as $data_client)
                                            <?php
                                            $impo = floatval($data_client->importe);
                                            $porc1 = floatval($impo)*100/max(floatval($tot_ctas_cobrar),1);
                                            $acum_1+=floatval($impo);

                                            ?>
                                        <tr>
                                            <td><a href="#" onclick="muestra_detalle3('{{$data_client->concepto}}')">{{$data_client->concepto}}</a></td>
                                            <td style="text-align: right">{{'$'.number_format($impo,2)}}</td>
                                            <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <td style="font-weight: bold">TOTAL :</td>
                                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($acum_1,2)}}</td>
                                    <td></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!-------------------------Detalle 3--------------------------------->
        <sl-dialog label="" id="dialog6" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" id="table_3" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Cliente</th>
                                        <th>Póliza</th>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Importe</th>
                                        <th>Pagos</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td  colspan="4" style="font-weight: bold">TOTALES :</td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table3"></label></td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table3_p"></label></td>
                                    </tr>
                                    <tr>
                                        <td  colspan="5" style="font-weight: bold">SALDO :</td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table3_p_t"></label></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!----------------------------------Cuentas_x_Pagar-------------------------------->
        <sl-dialog label="Cuentas por Pagar: Enero - {{$mes_let}} - {{$eje_act}}" id="dialog7" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Proveedor</th>
                                        <th>Importe</th>
                                        <th>% del mes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $acum_1 = 0;?>
                                    @foreach($ctas_pagar as $data_client)
                                            <?php
                                            $impo = floatval($data_client->importe);
                                            $porc1 = floatval($impo)*100/max(floatval($tot_ctas_cobrar),1);
                                            $acum_1+=floatval($impo);

                                            ?>
                                        <tr>
                                            <td><a href="#" onclick="muestra_detalle4('{{$data_client->concepto}}')">{{$data_client->concepto}}</a></td>
                                            <td style="text-align: right">{{'$'.number_format($impo,2)}}</td>
                                            <td style="text-align: right">{{number_format($porc1,2).'%'}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <td style="font-weight: bold">TOTAL :</td>
                                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($acum_1,2)}}</td>
                                    <td></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
        <!-------------------------Detalle 3--------------------------------->
        <sl-dialog label="" id="dialog8" style="--width: 60rem !important;--height: 30rem !important;">
            <div class="row">
                <sl-card>
                    <div class="card-body">
                        <div class="container-card">
                            <div>
                                <table class="table table-striped" id="table_4" style="width: 100%">
                                    <thead>
                                    <tr style="background-color: black; color: white">
                                        <th>Proveedor</th>
                                        <th>Póliza</th>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Importe</th>
                                        <th>Pagos</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td  colspan="4" style="font-weight: bold">TOTALES :</td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table4"></label></td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table4_p"></label></td>
                                    </tr>
                                    <tr>
                                        <td  colspan="5" style="font-weight: bold">SALDO :</td>
                                        <td style="text-align: right;font-weight: bold"><label id="total_table4_p_t"></label></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </sl-card>
            </div>
        </sl-dialog>
    </body>
</html>

