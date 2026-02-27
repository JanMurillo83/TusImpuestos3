<x-filament-panels::page>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
@php
    $fmtMoney = fn ($v) => '$' . number_format((float) $v, 2);
    $fmtPct = fn ($v, $d = 1) => number_format(((float) $v) * 100, $d) . '%';
    $lossMax = ($loss_reasons ?? collect())->max('importe') ?: 0;
@endphp

<div class="bg-slate-200 -mt-8 -mx-4 pb-6">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-full px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Dashboard Comercial</h1>
                <p class="text-xs text-slate-500">Indicadores clave del periodo</p>
            </div>
            <div class="text-xs text-slate-500 text-right">
                <p>Fecha: {{$fecha}}</p>
                <p>Periodo: <span class="font-semibold">{{$mes_actual}} {{$ejercicio}}</span></p>
                @if($seller_only)
                    <p class="text-emerald-600 font-semibold">Vista vendedor</p>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-full mx-auto px-6 py-6 space-y-6">
        <section>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">KPIs comerciales</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 text-sm">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Cotizaciones</p>
                    <p class="text-2xl font-bold text-slate-800">{{$kpis['total_quotes']}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">{{$kpis['open_count']}} abiertas · {{$kpis['invoiced_quotes_count']}} facturadas · {{$kpis['lost_count']}} perdidas/expiradas</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">$ Cotizado</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtMoney($kpis['total_quoted'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Base para conversión ponderada</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">$ Facturado (total)</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtMoney($kpis['total_invoiced'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">{{$fmtMoney($kpis['invoiced_from_quotes_value'])}} desde cotizaciones · {{$fmtMoney($kpis['invoiced_direct_value'])}} directo</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Conversión</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtPct($kpis['conversion'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Cotizaciones facturadas / totales</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Conversión ponderada</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtPct($kpis['weighted'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">$ facturado desde cotizaciones / $ cotizado</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Ciclo promedio</p>
                    <p class="text-2xl font-bold text-slate-800">{{number_format($kpis['avg_cycle'], 1)}} días</p>
                    <p class="text-[11px] text-slate-500 mt-1">De cotización a factura</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Descuento promedio</p>
                    <p class="text-2xl font-bold text-slate-800">{{number_format($kpis['avg_discount'], 1)}}%</p>
                    <p class="text-[11px] text-slate-500 mt-1">Control de calidad comercial</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Margen ponderado</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtPct($kpis['margin_pct_weighted'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Requiere costo en partidas</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Cobranza ponderada</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtPct($kpis['paid_pct_weighted'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">1 - (pendiente/total)</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Ventas directas</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtMoney($kpis['invoiced_direct_value'])}}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Facturas sin cotización</p>
                </div>
            </div>
        </section>

        <section>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Productos</p>
                    <p class="text-2xl font-bold text-slate-800">{{$productos_count}}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Almacenes</p>
                    <p class="text-2xl font-bold text-slate-800">{{$almacenes_count}}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Movimientos</p>
                    <p class="text-2xl font-bold text-slate-800">{{$movimientos_count}}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Inventario valuado (aprox)</p>
                    <p class="text-2xl font-bold text-slate-800">{{$fmtMoney($inventario_valuado)}}</p>
                </div>
            </div>
        </section>

        <section>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Alertas: Bajo stock</h3>
                        <p class="text-[11px] text-slate-500">Productos por debajo del mínimo configurado.</p>
                    </div>
                    <a href="{{'/'.$team_id.'/inventario-detalle'}}" class="text-xs text-slate-600 underline">Ver reportes</a>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-[11px] text-slate-800">
                        <thead>
                        <tr class="text-slate-600 bg-slate-50">
                            <th class="py-2 px-3 text-left">Almacén</th>
                            <th class="py-2 px-3 text-left">SKU</th>
                            <th class="py-2 px-3 text-left">Producto</th>
                            <th class="py-2 px-3 text-right">Existencia</th>
                            <th class="py-2 px-3 text-right">Mínimo</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($low_stock as $row)
                            @php
                                $minVal = $row->minimo ?? $row->min ?? $row->minimo_stock ?? $row->min_stock ?? 0;
                                $almacenLabel = $row->almacen ?? $row->almacen_id ?? '—';
                            @endphp
                            <tr class="border-t">
                                <td class="py-2 px-3 text-slate-800">{{$almacenLabel}}</td>
                                <td class="py-2 px-3 text-slate-800">{{$row->clave}}</td>
                                <td class="py-2 px-3 text-slate-800">{{$row->descripcion}}</td>
                                <td class="py-2 px-3 text-right text-slate-800">{{number_format((float) $row->exist, 0)}}</td>
                                <td class="py-2 px-3 text-right text-slate-800">{{number_format((float) $minVal, 0)}}</td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td class="py-3 px-3 text-center text-slate-500" colspan="5">Sin alertas en el periodo.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
</x-filament-panels::page>
