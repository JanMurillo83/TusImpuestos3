<x-filament-panels::page>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
<div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm">
            <div class="max-w-full px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800">Dashboard de Inicio</h1>
                    <p class="text-xs text-slate-500">Indicadores clave del periodo</p>
                </div>
                <div class="text-xs text-slate-500 text-right">
                    <p>Fecha: {{$fecha}}</p>
                    <p>Periodo de Trabajo: <span class="font-semibold">{{$mes_actual}} {{$ejercicio}}</span></p>
                </div>
            </div>
        </header>

        <main class="max-w-full mx-auto px-6 py-6 space-y-6">
            <section>
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Resumen del periodo</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-emerald-500">Total cotizaciones del periodo</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{'$'.number_format($cotizaciones_periodo,2)}}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-amber-500">Cotizaciones pendientes de facturar</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{'$'.number_format($cotizaciones_pendientes_importe,2)}}</p>
                        <p class="text-[11px] text-slate-500 mt-1">{{$cotizaciones_pendientes_count}} cotizaciones</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-blue-500">Facturas timbradas del periodo</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{'$'.number_format($facturas_timbradas_periodo,2)}}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Utilidad total del periodo</p><br>
                        <p class="text-2xl font-bold text-emerald-600">{{'$'.number_format($utilidad_periodo,2)}}</p>
                    </div>
                </div>
            </section>

            <section>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-indigo-500">Costo total del inventario</p><br>
                        <p class="text-2xl font-bold text-amber-600">{{'$'.number_format($costo_inventario,2)}}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-rose-500">Ordenes de compra pendientes</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{$ordenes_pendientes}}</p>
                        <p class="text-[11px] text-slate-500 mt-1">{{'$'.number_format($ordenes_pendientes_importe,2)}} en total</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-emerald-500">Compras del periodo</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{'$'.number_format($compras_periodo,2)}}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Periodo</p><br>
                        <p class="text-2xl font-bold text-slate-800">{{$mes_actual}}</p>
                        <p class="text-[11px] text-slate-500 mt-1">{{$ejercicio}}</p>
                    </div>
                </div>
            </section>

            <section>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Importe de cotizaciones por vendedor</h3>
                            <p class="text-[11px] text-slate-500">{{$mes_actual}} {{$ejercicio}}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">Periodo actual</span>
                    </div>
                    <table class="w-full text-[11px]">
                        <thead>
                        <tr class="text-slate-500">
                            <th class="py-1 text-left" style="color: black !important;">Vendedor</th>
                            <th class="py-1 text-right" style="color: black !important;">Importe</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($cotizaciones_por_vendedor as $row)
                            <tr class="border-t">
                                <td class="py-1 pr-2" style="color: black !important;">{{$row['nombre']}}</td>
                                <td class="py-1 text-right" style="color: black !important;">{{'$'.number_format($row['importe'],2)}}</td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td class="py-2 text-center text-slate-500" colspan="2">Sin cotizaciones en el periodo.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
</x-filament-panels::page>
