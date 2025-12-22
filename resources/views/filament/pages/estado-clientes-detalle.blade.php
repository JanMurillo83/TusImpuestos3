<x-filament-panels::page>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
    <div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
        <div class="max-w-full mx-auto my-8 bg-white p-8 rounded-xl shadow">
            <!-- Header -->
            <header class="border-b pb-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">
                            Estado de Cuenta de Cliente
                        </h1>
                        <p class="text-xs text-slate-500">
                            {{$empresa}} · Módulo de Cuentas por Cobrar
                        </p>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-600">
                    <p><span class="font-semibold">Fecha de emisión:</span> {{\Carbon\Carbon::now()->format('d-m-Y')}}</p>
                    <p><span class="font-semibold">Usuario:</span> {{\Illuminate\Support\Facades\Auth::user()->name}}</p>
                </div>
            </header>

            <!-- Datos Cliente -->
            <section class="mt-4 bg-slate-50 border border-slate-200 rounded-lg p-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="font-semibold text-slate-700">👤 Cliente</p>
                    <p class="text-slate-700">{{$maindata->cliente}}</p>
                    <p class="mt-1 text-xs text-slate-500">RFC: {{$datacliente?->rfc ?? 'XAXX010101000'}} · No. Cliente: {{$datacliente?->id ?? '0000'}}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-slate-700">🕒 Periodo</p>
                    <p class="text-slate-700">Enero – {{$mes_actual}}</p>
                    <p class="mt-1 text-xs text-slate-500">Moneda: MXN</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-700">📧 Contacto</p>
                    <p class="text-slate-700">{{$datacliente?->contacto ?? 'xxxxxxxx'}}</p>
                    <p class="text-xs text-slate-500">{{$datacliente?->correo ?? 'xxxxxxxx'}} · {{$datacliente?->telefono ?? 'xxxxxxxx'}}</p>
                </div>
            </section>

            <!-- Resumen -->
            <section class="mt-4 grid grid-cols-4 gap-3 text-sm">
                <div class="bg-white border rounded-lg p-3 shadow-sm">
                    <p class="text-xs text-slate-500">Saldo Total</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{'$'.number_format($maindata->saldo,2)}}</p>
                </div>
                <div class="bg-white border rounded-lg p-3 shadow-sm">
                    <p class="text-xs text-slate-500">Saldo al Corriente</p>
                    <p class="text-lg font-semibold text-green-800 mt-1">{{'$'.number_format($maindata->corriente,2)}}</p>
                </div>
                <div class="bg-white border rounded-lg p-3 shadow-sm">
                    <p class="text-xs text-slate-500">Saldo Vencido</p>
                    <p class="text-lg font-semibold text-red-800 mt-1">{{'$'.number_format($maindata->vencido,2)}}</p>
                </div>
                <div class="bg-orange-500 text-white rounded-lg p-3 shadow-sm">
                    <p class="text-xs">Porcentaje Saldo Vencido</p>
                    <p class="text-lg font-bold mt-1">{{number_format((floatval($maindata->vencido) * 100 / max(floatval($maindata->saldo),1)),2).'%'}}</p>
                </div>
            </section>

            <!-- Tabla -->
            <section class="mt-6">
                <table class="w-full text-xs border-collapse">
                    <thead>
                    <tr class="bg-slate-100 text-slate-700">
                        <th class="py-2 px-2 text-left">Fecha</th>
                        <th class="py-2 px-2 text-left">Doc.</th>
                        <th class="py-2 px-2 text-left">Folio</th>
                        <th class="py-2 px-2 text-left">Descripción</th>
                        <th class="py-2 px-2 text-left">Vence</th>
                        <th class="py-2 px-2 text-right">Días atraso</th>
                        <th class="py-2 px-2 text-right">Cargo</th>
                        <th class="py-2 px-2 text-right">Abono</th>
                        <th class="py-2 px-2 text-right">Saldo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        $cargos = 0;
                        $abonos = 0;
                    ?>
                    @foreach($facturas as $factura)
                    <?php
                            $uuid = $factura['uuid'] ?? '';
                            if($uuid != ''){
                                $xml = \App\Models\Almacencfdis::where('UUID',$uuid)->first()->content;
                                $xml = \CfdiUtils\Cleaner\Cleaner::staticClean($xml);
                                $xml = \CfdiUtils\Cfdi::newFromString($xml);
                                $cfdi = $xml->getQuickReader();
                                $conceptos = $cfdi->Conceptos;
                                $descripcion = '';
                                foreach ($conceptos() as $concepto)
                                {
                                    $descripcion.= $concepto['Descripcion'].' ';
                                }
                            }
                            $fecha = \Carbon\Carbon::create($factura['fecha'])->format('Y-m-d');
                            $vencimiento = \Carbon\Carbon::create($factura['vencimiento'])->format('Y-m-d');
                            $now = \Carbon\Carbon::now();
                            if($vencimiento < $now) {
                                $dif = (\Carbon\Carbon::create($factura['vencimiento'])->diff($now)->days < 1)
                                    ? '0'
                                    : \Carbon\Carbon::create($factura['vencimiento'])->diff($now)->days;
                                //if($dif < 0) $dif = 0;
                            }else{
                                $dif = 0;
                            }
                            $cargos+= floatval($factura['importe']);
                            $abonos+= floatval($factura['pagos']);
                    ?>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 px-2 text-slate-700">{{\Carbon\Carbon::create($factura['fecha'])->format('d-m-Y')}}</td>
                        <td class="py-1.5 px-2 text-slate-700">Factura</td>
                        <td class="py-1.5 px-2 text-slate-700">{{$factura['factura']}}</td>
                        <td class="py-1.5 px-2 text-slate-700">{{$descripcion}}</td>
                        <td class="py-1.5 px-2 text-slate-700">{{\Carbon\Carbon::create($factura['vencimiento'])->format('d-m-Y')}}</td>
                        <td class="py-1.5 px-2 text-right text-slate-700">{{$dif}}</td>
                        <td class="py-1.5 px-2 text-right text-slate-700">{{'$'.number_format($factura['importe'],2)}}</td>
                        <td class="py-1.5 px-2 text-right text-slate-700">{{'$'.number_format($factura['pagos'],2)}}</td>
                        <td class="py-1.5 px-2 text-right text-slate-700">{{'$'.number_format($factura['saldo'],2)}}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <!-- Resumen final -->
            <section class="mt-4 flex justify-end">
                <div class="w-64 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs">
                    <div class="flex justify-between text-slate-700">
                        <span>Total cargos</span>
                        <span>{{'$'.number_format($cargos,2)}}</span>
                    </div>
                    <div class="flex justify-between text-slate-700">
                        <span>Total abonos</span>
                        <span>{{'$'.number_format($abonos,2)}}</span>
                    </div>
                    <div class="flex justify-between  text-slate-700">
                        <span>Saldo vencido</span>
                        <span class="text-red-600">{{'$'.number_format($maindata->vencido,2)}}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t flex justify-between font-semibold  text-slate-700">
                        <span>Total a pagar</span>
                        <span class="text-orange-600">{{'$'.number_format($maindata->saldo,2)}}</span>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="mt-6 pt-4 border-t text-[10px] text-slate-500 flex justify-between">
                <p>
                    Para aclaraciones: {{$emp_correo}} · {{$emp_telefono}}<br>

                </p>
                <p class="text-right">
                    {{$empresa}}<br>
                    Departamento de Crédito y Cobranza
                </p>
            </footer>
        </div>
    </div>
</x-filament-panels::page>
