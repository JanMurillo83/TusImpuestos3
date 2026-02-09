
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
                            Detalle del Inventario
                        </h1>
                        <p class="text-xs text-slate-500">
                            {{$empresa}}
                        </p>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-600 space-y-1">
                    <p><span class="font-semibold">Fecha de emisión:</span> {{\Carbon\Carbon::now()->format('d-m-Y')}}</p>
                    <p><span class="font-semibold">Usuario:</span> {{\Illuminate\Support\Facades\Auth::user()->name}}</p>
                    <p><span class="font-semibold">Periodo:</span> {{$mes_letras}} {{$ejercicio}}</p>
                </div>
            </header>

            <!-- Botones de envío (WhatsApp y correo) -->
            <section class="mt-4 flex justify-end gap-2 text-xs print:hidden">
                <a
                    href="https://wa.me/TU_NUMERO?text=Te%20comparto%20el%20reporte%20de%20inventario."
                    target="_blank"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-green-500 hover:bg-green-600 text-white font-semibold"
                >
                    <span>📲</span>
                    <span>Enviar por WhatsApp</span>
                </a>

                <a
                    href="mailto:?subject=Reporte%20de%20Inventario&body=Te%20comparto%20el%20reporte%20de%20inventario."
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-semibold"
                >
                    <span>✉️</span>
                    <span>Enviar por correo</span>
                </a>
            </section>

            <section class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">📦 Total artículos</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{intval(count($inventario_data))}}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">📊 Existencias totales</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1">{{number_format($inventario_data->sum('exist'),2)}}</p>
                </div>
                <div class="bg-amber-500 text-white rounded-lg p-3">
                    <p class="text-xs">💰 Costo total del inventario</p>
                    <p class="text-lg font-bold mt-1">{{'$'.number_format($costo_total,2)}}</p>
                </div>
            </section>

            <!-- Tabla de inventario -->
            <section class="mt-6">
                <div class="flex justify-between items-center mb-2 text-xs text-slate-500">
                    <p>Detalle por Artículo</p>
                    <p>Montos en MXN</p>
                </div>

                <div class="overflow-x-auto">
                    <div class="col-span-2">
                        <table class="w-full text-[11px]">
                            <thead>
                            <tr class="text-slate-500 bg-slate-100">
                                <th class="py-2 px-2 text-left" style="color: black !important;">Clave</th>
                                <th class="py-2 px-2 text-left" style="color: black !important;">Descripción</th>
                                <th class="py-2 px-2 text-right" style="color: black !important;">Costo Promedio</th>
                                <th class="py-2 px-2 text-right" style="color: black !important;">Existencia</th>
                                <th class="py-2 px-2 text-right" style="color: black !important;">Costo Total</th>
                                <th class="py-2 px-2 text-right" style="color: black !important;">% del Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($inventario_data as $item)
                                    <?php
                                    $importe_total = floatval($item->p_costo) * floatval($item->exist);
                                    $porcentaje = $importe_total * 100 / max($costo_total, 1);
                                    ?>
                                <tr class="border-t hover:bg-slate-50">
                                    <td class="py-2 px-2" style="color: black !important;">{{$item->clave}}</td>
                                    <td class="py-2 px-2" style="color: black !important;">{{$item->descripcion}}</td>
                                    <td class="py-2 px-2 text-right" style="color: black !important;">{{'$'.number_format($item->p_costo, 2)}}</td>
                                    <td class="py-2 px-2 text-right" style="color: black !important;">{{number_format($item->exist, 2)}}</td>
                                    <td class="py-2 px-2 text-right font-semibold" style="color: black !important;">{{'$'.number_format($importe_total, 2)}}</td>
                                    <td class="py-2 px-2 text-right" style="color: black !important;">{{number_format($porcentaje, 2).'%'}}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-slate-400 bg-slate-100 font-bold">
                                <td class="py-2 px-2" style="color: black !important;" colspan="4">TOTAL</td>
                                <td class="py-2 px-2 text-right" style="color: black !important;">{{'$'.number_format($costo_total, 2)}}</td>
                                <td class="py-2 px-2 text-right" style="color: black !important;">100.00%</td>
                            </tr>
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
                    Departamento de Administración<br>
                    {{$emp_correo}} · {{$emp_telefono}}
                </p>
            </footer>
        </div>
    </div>
</x-filament-panels::page>
