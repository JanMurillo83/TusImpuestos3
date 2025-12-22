<x-filament-panels::page>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
    <div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
        <div class="max-w-full mx-auto my-8 bg-white p-8 rounded-xl shadow">

            <!-- Header -->
            <header class="flex justify-between items-start border-b pb-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">
                            Ventas del Ejercicio
                        </h1>
                        <p class="text-xs text-slate-500">
                            {{$empresa}}
                        </p>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-600 space-y-1">
                    <p><span class="font-semibold">Fecha de emisión:</span> {{\Carbon\Carbon::now()->format('d-m-Y')}}</p>
                    <p><span class="font-semibold">Usuario:</span> {{\Illuminate\Support\Facades\Auth::user()->name}}</p>
                </div>
            </header>

            <!-- Botones de envío (WhatsApp y correo) -->
            <section class="mt-4 flex justify-end gap-2 text-xs print:hidden">
                <!-- WhatsApp: reemplaza TU_NUMERO y la URL del reporte de cartera -->
                <a
                    href="https://wa.me/TU_NUMERO?text=Te%20comparto%20el%20reporte%20de%20saldos%20de%20clientes%20correspondiente%20al%20periodo%2001/11/2025%20-%2030/11/2025.%20Puedes%20consultarlo%20en%3A%20https%3A%2F%2Ftu-sistema.com%2Freporte%2Fcartera-clientes-2025-11"
                    target="_blank"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-green-500 hover:bg-green-600 text-white font-semibold"
                >
                    <span>📲</span>
                    <span>Enviar por WhatsApp</span>
                </a>

                <!-- Correo: mailto con asunto y cuerpo -->
                <a
                    href="mailto:?subject=Reporte%20de%20saldos%20de%20clientes&body=Te%20comparto%20el%20reporte%20de%20saldos%20de%20clientes%20correspondiente%20al%20periodo%2001/11/2025%20-%2030/11/2025.%0APuedes%20consultarlo%20en:%20https://tu-sistema.com/reporte/cartera-clientes-2025-11"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-semibold"
                >
                    <span>✉️</span>
                    <span>Enviar por correo</span>
                </a>
            </section>

            <section class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">👥 Total clientes</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{intval(count($maindata))}}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">💰 Ventas del Ejercicio</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{'$'.number_format($importe_mes,2)}}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">⏳ Ventas ejercicio Anterior</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{'$'.number_format($importe_ant,2)}}</p>
                </div>
                <div class="bg-orange-500 text-white rounded-lg p-3">
                    <p class="text-xs">📊 % Incremento</p>
                    <?php
                    $por_in = $importe_mes * 100 / max($importe_ant,1);
                    ?>
                    <p class="text-lg font-bold mt-1">{{number_format($por_in,2).'%'}}</p>
                </div>
            </section>

            <!-- Tabla de saldos por cliente -->
            <section class="mt-6">
                <div class="flex justify-between items-center mb-2 text-xs text-slate-500">
                    <p>Detalle por Cliente</p>
                    <p>Montos en MXN</p>
                </div>

                <div class="overflow-x-auto">
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
                            @foreach($maindata as $data)
                                    <?php
                                    $impo = floatval($data->importe);
                                    $porc = $impo * 100 /max($importe_mes,1);
                                    ?>
                                <tr class="border-t">
                                    <td class="py-1 pr-2" style="color: black !important;">{{$data->cliente}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($data->importe,2)}}</td>
                                    <td class="py-1 text-right" style="color: black !important;">{{number_format($porc,2).'%'}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="mt-6 pt-4 border-t text-[10px] text-slate-500 flex justify-between">
                <p>
                    Reporte generado automáticamente por Tus-Impuestos.com<br>
                    La información está sujeta a la captura y conciliación al día de hoy.
                </p>
                <p class="text-right">
                    Departamento de Ventas<br>
                    {{$emp_correo}} · {{$emp_telefono}}
                </p>
            </footer>
        </div>
    </div>
</x-filament-panels::page>
