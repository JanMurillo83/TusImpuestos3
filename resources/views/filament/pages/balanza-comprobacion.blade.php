<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtros --}}
        <x-filament::section>
            <x-slot name="heading">
                Selección de Periodo
            </x-slot>

            <form wire:submit.prevent="cargarBalanza">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{ $this->form }}

                    <div class="flex items-end">
                        <x-filament::button
                            type="submit"
                            icon="heroicon-o-arrow-path"
                            wire:loading.attr="disabled"
                        >
                            Actualizar
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </x-filament::section>

        {{-- Información del reporte --}}
        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">
                        Balanza de Comprobación
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Ejercicio: {{ $ejercicio }} | Periodo: {{ str_pad($periodo, 2, '0', STR_PAD_LEFT) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        Total de cuentas: {{ count($balanza) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Tabla de balanza --}}
        @if(count($balanza) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse border border-gray-300 dark:border-gray-600">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-left font-semibold text-xs uppercase">Código</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-left font-semibold text-xs uppercase">Cuenta</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-center font-semibold text-xs uppercase">Saldo Inicial</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-center font-semibold text-xs uppercase">Cargos</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-center font-semibold text-xs uppercase">Abonos</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-center font-semibold text-xs uppercase">Saldo Final</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($balanza as $cuenta)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50
                                @if($cuenta['nivel'] == 1) bg-gray-50 dark:bg-gray-900/50 font-semibold
                                @elseif($cuenta['nivel'] == 2) bg-gray-25 dark:bg-gray-900/30 font-medium
                                @endif">
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 whitespace-nowrap text-xs">
                                    <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 12 }}px;">
                                        {{ $cuenta['codigo'] }}
                                    </span>
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs">
                                    <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 12 }}px;">
                                        {{ $cuenta['cuenta'] }}
                                    </span>
                                </td>
                                {{-- Saldo Inicial --}}
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    {{ $cuenta['saldo_anterior'] != 0 ? '$'.number_format($cuenta['saldo_anterior'], 2) : '-' }}
                                </td>
                                {{-- Movimientos --}}
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    {{ $cuenta['cargos'] != 0 ? '$'.number_format($cuenta['cargos'], 2) : '-' }}
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    {{ $cuenta['abonos'] != 0 ? '$'.number_format($cuenta['abonos'], 2) : '-' }}
                                </td>
                                {{-- Saldo Final --}}
                                <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 text-right whitespace-nowrap text-xs font-mono {{ $cuenta['saldo_final'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $cuenta['saldo_final'] != 0 ? '$'.number_format($cuenta['saldo_final'], 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-200 dark:bg-gray-700 border-t-2 border-gray-400 dark:border-gray-500 font-bold">
                        <tr>
                            <td colspan="2" class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-right uppercase text-sm">TOTALES:</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-right text-sm font-mono">
                                @php
                                    $saldo_inicial_neto = $totales['saldo_ant_deudor'] - $totales['saldo_ant_acreedor'];
                                @endphp
                                ${{ number_format($saldo_inicial_neto, 2) }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-right text-sm font-mono">
                                ${{ number_format($totales['cargos'], 2) }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-right text-sm font-mono">
                                ${{ number_format($totales['abonos'], 2) }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-right text-sm font-mono">
                                @php
                                    $saldo_final_neto = $totales['saldo_deudor'] - $totales['saldo_acreedor'];
                                @endphp
                                ${{ number_format($saldo_final_neto, 2) }}
                            </td>
                        </tr>
                        @php
                            $diferencia = $totales['saldo_deudor'] - $totales['saldo_acreedor'];
                        @endphp
                        @if(abs($diferencia) > 0.01)
                            <tr class="bg-warning-100 dark:bg-warning-900/30">
                                <td colspan="5" class="px-3 py-2 text-right uppercase text-sm">
                                    ⚠️ Diferencia:
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-mono text-warning-700 dark:text-warning-400">
                                    ${{ number_format(abs($diferencia), 2) }}
                                </td>
                            </tr>
                        @else
                            <tr class="bg-success-100 dark:bg-success-900/30">
                                <td colspan="3" class="px-3 py-2 text-right uppercase text-xs text-success-700 dark:text-success-400">
                                    ✓ Balanza cuadrada
                                </td>
                                <td colspan="3" class="px-3 py-2 text-center text-sm text-success-700 dark:text-success-400">
                                    Debe ser igual a Haber
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
            <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex flex-wrap gap-4">
                    <div>
                        <span class="font-semibold">Saldo Inicial:</span>
                        Deudor ${{ number_format($totales['saldo_ant_deudor'], 2) }} /
                        Acreedor ${{ number_format($totales['saldo_ant_acreedor'], 2) }}
                    </div>
                    <div>
                        <span class="font-semibold">Saldo Final:</span>
                        Deudor ${{ number_format($totales['saldo_deudor'], 2) }} /
                        Acreedor ${{ number_format($totales['saldo_acreedor'], 2) }}
                    </div>
                    <div>
                        <span class="font-semibold">Diferencia Final:</span>
                        ${{ number_format(abs($diferencia), 2) }}
                    </div>
                </div>
            </div>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">
                        📊
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        No hay datos disponibles
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron movimientos para el ejercicio {{ $ejercicio }} periodo {{ str_pad($periodo, 2, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
            </x-filament::section>
        @endif

        {{-- Notas --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                📝 Notas sobre la Balanza de Comprobación
            </x-slot>

            <div class="text-sm space-y-2">
                <p><strong>Saldo Anterior:</strong> Saldo acumulado al inicio del periodo. Para cuentas de Balance, incluye ejercicios anteriores. Para cuentas de Resultados, solo incluye el acumulado del ejercicio actual.</p>
                <p><strong>Cargos:</strong> Suma de cargos del periodo seleccionado.</p>
                <p><strong>Abonos:</strong> Suma de abonos del periodo seleccionado.</p>
                <p><strong>Saldo Final:</strong> Resultado de Saldo Anterior + Cargos - Abonos. Se muestra en verde cuando es positivo (deudor) y en rojo cuando es negativo (acreedor).</p>
                <p class="mt-4 text-info-600 dark:text-info-400">
                    <strong>ℹ️ Nota:</strong> La balanza debe cuadrar cuando la suma de Saldos Deudores es igual a la suma de Saldos Acreedores. Si hay diferencia significativa, revisar movimientos.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
