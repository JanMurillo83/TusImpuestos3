<x-filament-panels::page>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
<div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
    <div class="min-h-screen">
        <!-- Barra superior -->
        <header class="bg-white shadow-sm">
            <div class="max-w-full px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-lg font-bold text-slate-800">
                            Dashboard general
                        </h1>
                        <p class="text-xs text-slate-500">
                            Resumen ejecutivo del negocio
                        </p>
                    </div>
                </div>
                <div class="text-xs text-slate-500 text-right">
                    <p>Usuario: <span class="font-semibold">{{$usuario}}</span></p>
                    <p>Fecha: {{$fecha}}</p>
                    <p>Periodo de Trabajo: <span class="font-semibold">{{$mes_actual}}</span></p>
                </div>
            </div>
        </header>

        <main class="max-w-full mx-auto px-6 py-6 space-y-6">
            <!-- RESUMEN GENERAL -->
            <section>
                <h2 class="text-sm font-semibold text-slate-700 mb-2">
                    Resumen general del negocio
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
                    <!-- Ventas del mes -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-emerald-500">Ventas del mes</p><br>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{'$'.number_format($ventas,2)}}</p><br>
                        <a href="/{{$team_id}}/ventasperiododetalle" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                    <!-- Ventas del año -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-emerald-500">Ventas del año</p><br>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{'$'.number_format($ventas_anuales,2)}}</p><br>
                        <a href="#" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                    <!-- Cuentas por cobrar -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-amber-500">Cuentas por Cobrar</p><br>
                        <p class="text-2xl font-bold text-green-600 mt-1">{{'$'.number_format($cobrar_importe,2)}}</p><br>
                        <a href="#" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                    <!-- Cartera Vencida-->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-red-500">Cartera Vencida</p><br>
                        <p class="text-2xl font-bold text-red-400 mt-1">{{'$'.number_format($importe_vencido,2)}}</p><br>
                        <a href="#" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-blue-500">Cuentas por Pagar</p><br>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{'$'.number_format($pagar_importe,2)}}</p><br>
                        <a href="#" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                </div>
            </section>

            <!-- UTILIDAD / IMPUESTOS -->
            <section>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
                    <!-- Cuentas por pagar -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-blue-500">Inventario</p><br>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{'$'.number_format($importe_inventario,2)}}</p><br>
                        <a href="#" class="text-xs px-10 py-1 rounded-full bg-slate-100 text-slate-600">Ver Detalle >></a>
                    </div>
                    <!-- Utilidad del periodo -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Utilidad del periodo</p><br>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{'$'.number_format($utilidad_importe,2)}}</p><br>
                        <p class="text-[11px] text-slate-500 mt-1">Periodo: {{$mes_actual}}-{{$ejercicio}}</p>
                    </div>

                    <!-- Utilidad del ejercicio -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Utilidad acumulada del ejercicio</p><br>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{'$'.number_format($utilidad_ejercicio,2)}}</p><br>
                        <p class="text-[11px] text-slate-500 mt-1">Enero – {{$mes_actual}} {{$ejercicio}}</p>
                    </div>

                    <!-- Impuesto anual (30%) -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Impuesto anual</p><br>
                        <p class="text-2xl font-bold text-orange-500 mt-1">{{'$'.number_format($impuesto_estimado,2)}}</p><br>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Calculado utilidad del ejercicio * 30%
                        </p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-2 shadow-sm">
                        <p class="text-xs text-amber-500">Impuestos del mes</p>
                        <table class="w-full text-[11px]">
                            <thead>
                            <tr class="text-slate-500">
                                <th class="py-1 text-left" style="color: black !important;">Impuesto</th>
                                <th class="py-1 text-right" style="color: black !important;">Importe</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="border-t">
                                <td class="py-1 pr-2"  style="color: black !important;">ISR propio</td>
                                <td class="py-1 text-right"  style="color: black !important;">{{'$'.number_format($impuesto_mensual,2)}}</td>
                            </tr>
                            <tr class="border-t">
                                <td class="py-1 pr-2" style="color: black !important;">IVA</td>
                                <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($importe_iva,2)}}</td>
                            </tr>
                            <tr class="border-t">
                                <td class="py-1 pr-2" style="color: black !important;">Retenciones</td>
                                <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($importe_ret,2)}}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- FILA 1: VENTAS DEL MES / VENTAS DEL AÑO -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Ventas del mes (pastel por cliente) -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">
                                Ventas del mes por cliente
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Clientes · Importe · % del total mensual
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">
              {{$mes_actual}}-2025
            </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 items-center">
                        <!-- Aquí va la gráfica de pastel (placeholder) -->
                        <div class="col-span-1 flex items-center justify-center">
                            <div class="relative h-32 w-32 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center">
                <span class="text-[50px] text-emerald-900 text-center">
                  <i class="fa-solid fa-money-bill-trend-up"></i>
                </span>
                            </div>
                        </div>
                        <!-- Detalle de clientes -->
                        <div class="col-span-2">
                            <table class="w-full text-[11px]">
                                <thead>
                                <tr class="text-slate-500">
                                    <th class="py-1 text-left" style="color: black !important;">Cliente</th>
                                    <th class="py-1 text-right" style="color: black !important;">Importe</th>
                                    <th class="py-1 text-right" style="color: black !important;">% mes</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $impt_mes = 0; ?>
                                @foreach($mes_ventas_data as $data)
                                    <?php
                                        $impo = floatval($data->importe);
                                        $porc = $impo * 100 /max($ventas,1);
                                        $impt_mes+=$impo;
                                    ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">{{$data->concepto}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($data->importe,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc,2).'%'}}</td>
                                </tr>
                                @endforeach
                                <?php
                                $impt_mes_dif=$ventas-$impt_mes;
                                $porc_t = $impt_mes_dif * 100 /max($ventas,1);
                                ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">Otros Clientes</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($impt_mes_dif,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc_t,2).'%'}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Ventas del año (pastel top clientes) -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">
                                Ventas del año – Mejores clientes
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Top clientes · Importe vendido · % del total anual
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">
              Año 2025
            </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 items-center">
                        <!-- Pastel -->
                        <div class="col-span-1 flex items-center justify-center">
                            <div class="relative h-32 w-32 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center">
                <span class="text-[50px] text-slate-500 text-center">
                  <i class="fa-solid fa-calendar-days"></i>
                </span>
                            </div>
                        </div>
                        <!-- Tabla -->
                        <div class="col-span-2">
                            <table class="w-full text-[11px]">
                                <thead>
                                <tr class="text-slate-500">
                                    <th class="py-1 text-left" style="color: black !important;">Cliente</th>
                                    <th class="py-1 text-right" style="color: black !important;">Importe</th>
                                    <th class="py-1 text-right" style="color: black !important;">% año</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $impt_anio = 0; ?>
                                @foreach($anio_ventas_data as $data)
                                        <?php
                                        $impo = floatval($data->importe);
                                        $porc = $impo * 100 /max($ventas_anuales,1);
                                        $impt_anio+=$impo;
                                        ?>
                                    <tr class="border-t">
                                        <td class="py-1 pr-2" style="color: black !important;">{{$data->concepto}}</td>
                                        <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($data->importe,2)}}</td>
                                        <td class="py-1 text-right" style="color: black !important;">{{number_format($porc,2).'%'}}</td>
                                    </tr>
                                @endforeach
                                <?php
                                $impt_anio_dif=$ventas_anuales-$impt_anio;
                                $porc_t = $impt_anio_dif * 100 /max($ventas_anuales,1);
                                ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">Otros Clientes</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($impt_anio_dif,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc_t,2).'%'}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FILA 2: CxC / CARTERA VENCIDA CLIENTES -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Cuentas por cobrar -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">
                                Cuentas por cobrar
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Clientes · Importe · % de la cartera
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                          Corte: {{$fecha}}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 items-center">
                        <!-- Pastel -->
                        <div class="col-span-1 flex items-center justify-center">
                            <div class="relative h-32 w-32 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center">
                        <span class="text-[50px] text-slate-500 text-center">
                          <i class="fa-solid fa-hand-holding-dollar"></i>
                        </span>
                            </div>
                        </div>
                        <!-- Tabla -->
                        <div class="col-span-2">
                            <table class="w-full text-[11px]">
                                <thead>
                                <tr class="text-slate-500">
                                    <th class="py-1 text-left">Cliente</th>
                                    <th class="py-1 text-right">Saldo</th>
                                    <th class="py-1 text-right">% cartera</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $impt3 = 0; ?>
                                @foreach($cuentas_x_cobrar_top3 as $cliente)
                                    <?php $impt3+= floatval($cliente->saldo);
                                         $porc = floatval($cliente->saldo) * 100 / max($cobrar_importe,1);
                                    ?>
                                    <tr class="border-t">
                                        <td class="py-1 pr-2" style="color: black !important;">{{$cliente->cliente}}</td>
                                        <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($cliente->saldo,2)}}</td>
                                        <td class="py-1 text-right" style="color: black !important;">{{number_format($porc,2).'%'}}</td>
                                    </tr>
                                @endforeach
                                <?php
                                    $impt_o = $cobrar_importe - $impt3;
                                    $porc_t = $impt_o * 100 / max($cobrar_importe,1);
                                ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">Otros clientes</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($impt_o,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc_t,2).'%'}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cartera vencida clientes -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">
                                Cuentas por pagar – Proveedores
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Proveedores · Importe · % del total por pagar
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                          Corte: {{$fecha}}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 items-center">
                        <!-- Pastel -->
                        <div class="col-span-1 flex items-center justify-center">
                            <div class="relative h-32 w-32 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center">
                                <span class="text-[50px] text-amber-500 text-center">
                                  <i class="fa-solid fa-coins"></i>
                                </span>
                            </div>
                        </div>
                        <!-- Tabla -->
                        <div class="col-span-2">
                            <table class="w-full text-[11px]">
                                <thead>
                                <tr class="text-slate-500">
                                    <th class="py-1 text-left" style="color: black !important;">Proveedor</th>
                                    <th class="py-1 text-right" style="color: black !important;">Saldo</th>
                                    <th class="py-1 text-right" style="color: black !important;">% CxP</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $imptp = 0; ?>
                                @foreach($cuentas_x_pagar_top3 as $proveedor)
                                <?php
                                    $imptp+= floatval($proveedor->saldo);
                                    $porc = floatval($proveedor->saldo) * 100 / max($pagar_importe,1);
                                ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">{{$proveedor->cliente}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($proveedor->saldo,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc,2).'%'}}</td>
                                </tr>
                                @endforeach
                                <?php
                                $impt_p = $pagar_importe - $imptp;
                                $porc_tp = $impt_o * 100 / max($cobrar_importe,1);
                                ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">Otros Proveedores</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($impt_p,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc_tp,2).'%'}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
</x-filament-panels::page>
