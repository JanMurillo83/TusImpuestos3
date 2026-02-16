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
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 border-b-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-xs uppercase">Código</th>
                            <th class="px-3 py-3 text-left font-semibold text-xs uppercase">Cuenta</th>
                            <th class="px-3 py-3 text-right font-semibold text-xs uppercase">Saldo Anterior</th>
                            <th class="px-3 py-3 text-right font-semibold text-xs uppercase">Cargos</th>
                            <th class="px-3 py-3 text-right font-semibold text-xs uppercase">Abonos</th>
                            <th class="px-3 py-3 text-right font-semibold text-xs uppercase">Saldo Final</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($balanza as $cuenta)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50
                                @if($cuenta['nivel'] == 1) bg-gray-50 dark:bg-gray-900/50 font-semibold
                                @elseif($cuenta['nivel'] == 2) bg-gray-25 dark:bg-gray-900/30 font-medium
                                @endif">
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 12 }}px;">
                                        {{ $cuenta['codigo'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 12 }}px;">
                                        {{ $cuenta['cuenta'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    @if($cuenta['saldo_anterior'] != 0)
                                        <span class="{{ $cuenta['saldo_anterior'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                                            ${{ number_format(abs($cuenta['saldo_anterior']), 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    @if($cuenta['cargos'] != 0)
                                        ${{ number_format($cuenta['cargos'], 2) }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    @if($cuenta['abonos'] != 0)
                                        ${{ number_format($cuenta['abonos'], 2) }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap text-xs font-mono">
                                    @if($cuenta['saldo_final'] != 0)
                                        <span class="font-semibold {{ $cuenta['saldo_final'] < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                            ${{ number_format(abs($cuenta['saldo_final']), 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-200 dark:bg-gray-700 border-t-2 border-gray-400 dark:border-gray-500 font-bold">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-right uppercase text-sm">TOTALES:</td>
                            <td class="px-3 py-3 text-right text-sm font-mono">
                                ${{ number_format(abs($totales['saldo_anterior']), 2) }}
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-mono">
                                ${{ number_format($totales['cargos'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-mono">
                                ${{ number_format($totales['abonos'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-mono">
                                @php
                                    $saldoFinalTotal = $totales['saldo_anterior'] + $totales['cargos'] - $totales['abonos'];
                                @endphp
                                <span class="{{ $saldoFinalTotal < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                    ${{ number_format(abs($saldoFinalTotal), 2) }}
                                </span>
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
                                <td colspan="6" class="px-3 py-2 text-center text-sm text-success-700 dark:text-success-400">
                                    ✓ Balanza cuadrada
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
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
                <p><strong>Saldo Anterior:</strong> Saldo acumulado de periodos anteriores en el mismo ejercicio.</p>
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
