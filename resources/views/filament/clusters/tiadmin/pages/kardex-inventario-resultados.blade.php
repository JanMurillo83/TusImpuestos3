@php
    $grupos = $kardex['grupos'] ?? [];
    $totales = $kardex['totales'] ?? ['cant' => 0, 'costo' => 0, 'precio' => 0];
@endphp

<div class="space-y-6">
    @if(empty($grupos))
        <div class="p-4 bg-gray-100 rounded">
            No hay movimientos para los filtros seleccionados.
        </div>
    @else
        @foreach($grupos as $grupo)
            @php
                $producto = trim(($grupo['producto_clave'] ?? '') . ' - ' . ($grupo['producto_descripcion'] ?? ''));
                $movimientos = $grupo['movimientos'] ?? [];
                $sub = $grupo['totales'] ?? ['cant' => 0, 'costo' => 0, 'precio' => 0];
            @endphp
            <div class="border rounded-lg">
                <div class="px-4 py-2 bg-gray-50 font-semibold">
                    {{ $producto }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-2">Fecha</th>
                                <th class="text-left p-2">Tipo</th>
                                <th class="text-left p-2">Concepto</th>
                                <th class="text-right p-2">Cantidad</th>
                                <th class="text-right p-2">Costo</th>
                                <th class="text-right p-2">Precio</th>
                                <th class="text-right p-2">Total Costo</th>
                                <th class="text-right p-2">Total Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimientos as $mov)
                                <tr class="border-t">
                                    <td class="p-2">{{ \Carbon\Carbon::parse($mov['fecha'])->format('d-m-Y') }}</td>
                                    <td class="p-2">{{ $mov['tipo'] }}</td>
                                    <td class="p-2">{{ $mov['concepto'] }}</td>
                                    <td class="p-2 text-right">{{ number_format($mov['cant'], 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($mov['costo'], 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($mov['precio'], 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($mov['importe_costo'], 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($mov['importe_precio'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="p-2" colspan="3">Totales</td>
                                <td class="p-2 text-right">{{ number_format($sub['cant'], 2) }}</td>
                                <td class="p-2 text-right"></td>
                                <td class="p-2 text-right"></td>
                                <td class="p-2 text-right">{{ number_format($sub['costo'], 2) }}</td>
                                <td class="p-2 text-right">{{ number_format($sub['precio'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="border rounded-lg bg-gray-50 p-4 font-semibold">
            Gran total:
            <span class="ml-2">Cantidad {{ number_format($totales['cant'], 2) }}</span>
            <span class="ml-4">Total Costo {{ number_format($totales['costo'], 2) }}</span>
            <span class="ml-4">Total Precio {{ number_format($totales['precio'], 2) }}</span>
        </div>
    @endif
</div>
