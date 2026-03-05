<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">
                Vista previa
            </div>

            @if(empty($this->cuentas))
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    No hay movimientos para los filtros seleccionados.
                </div>
            @else
                <div class="space-y-6">
                    @foreach($this->cuentas as $cuenta)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}
                                <span class="ml-2 text-xs text-gray-500">
                                    Saldo inicial: {{ number_format($cuenta['saldo_inicial'], 2) }}
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-gray-100 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Fecha</th>
                                            <th class="px-3 py-2 text-left">Folio</th>
                                            <th class="px-3 py-2 text-left">Referencia</th>
                                            <th class="px-3 py-2 text-left">Concepto</th>
                                            <th class="px-3 py-2 text-right">Cargo</th>
                                            <th class="px-3 py-2 text-right">Abono</th>
                                            <th class="px-3 py-2 text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cuenta['movimientos'] as $mov)
                                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                                <td class="px-3 py-2">{{ $mov['fecha'] }}</td>
                                                <td class="px-3 py-2">{{ $mov['folio'] }}</td>
                                                <td class="px-3 py-2">{{ $mov['referencia'] }}</td>
                                                <td class="px-3 py-2">{{ $mov['concepto'] }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($mov['cargo'], 2) }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($mov['abono'], 2) }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($mov['saldo'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 text-xs text-right">
                                Saldo final: {{ number_format($cuenta['saldo_final'], 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 text-sm text-gray-700 dark:text-gray-200">
                    Totales del periodo: Cargos {{ number_format($this->totales['cargos'] ?? 0, 2) }} | Abonos {{ number_format($this->totales['abonos'] ?? 0, 2) }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
