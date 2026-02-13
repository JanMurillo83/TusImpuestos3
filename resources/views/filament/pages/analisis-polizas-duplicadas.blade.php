<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">Análisis de Pólizas con Folios Duplicados</h2>
                <p class="text-gray-600 mt-2">Revisa pólizas que comparten el mismo folio para determinar si son duplicados reales o errores de numeración</p>
            </div>
            <div class="flex gap-3">
                <x-filament::button wire:click="analizarDuplicados" icon="heroicon-o-arrow-path">
                    Actualizar
                </x-filament::button>
                @if($hayDuplicados)
                    <x-filament::button wire:click="exportar" icon="heroicon-o-arrow-down-tray" color="success">
                        Exportar JSON
                    </x-filament::button>
                @endif
            </div>
        </div>

        @if(!$hayDuplicados)
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-green-900">¡Todo en orden!</h3>
                <p class="text-green-700 mt-2">No se encontraron pólizas con folios duplicados</p>
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold text-yellow-900">Atención: Se encontraron {{ count($duplicados) }} grupos con folios duplicados</h3>
                        <p class="text-yellow-700 text-sm mt-1">Revisa cada caso para determinar si son duplicados reales o pólizas diferentes con el mismo folio por error</p>
                    </div>
                </div>
            </div>

            @foreach($duplicados as $grupo)
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">
                                    Tipo: <span class="text-blue-600">{{ $grupo['tipo'] }}</span> |
                                    Folio: <span class="text-blue-600">{{ $grupo['folio'] }}</span> |
                                    Periodo: <span class="text-blue-600">{{ $grupo['periodo'] }}/{{ $grupo['ejercicio'] }}</span>
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Team ID: {{ $grupo['team_id'] }}</p>
                            </div>
                            <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                {{ $grupo['cantidad'] }} pólizas
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($grupo['polizas'] as $index => $poliza)
                            <div class="p-6 hover:bg-gray-50">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-base font-semibold text-gray-900">
                                        Póliza #{{ $poliza['id'] }}
                                        <span class="text-xs font-normal text-gray-500">(Registro {{ $index + 1 }} de {{ $grupo['cantidad'] }})</span>
                                    </h4>
                                    <span class="text-xs text-gray-500">Creada: {{ $poliza['creado'] }}</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <span class="text-xs font-semibold text-gray-600">FECHA</span>
                                        <p class="text-sm">{{ $poliza['fecha'] }}</p>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <span class="text-xs font-semibold text-gray-600">CONCEPTO</span>
                                        <p class="text-sm">{{ $poliza['concepto'] }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-600">REFERENCIA</span>
                                        <p class="text-sm">{{ $poliza['referencia'] ?: 'Sin referencia' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-600">CARGOS</span>
                                        <p class="text-sm font-semibold text-green-700">${{ number_format($poliza['cargos'], 2) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-600">ABONOS</span>
                                        <p class="text-sm font-semibold text-red-700">${{ number_format($poliza['abonos'], 2) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-600">PARTIDAS</span>
                                        <p class="text-sm">{{ $poliza['partidas'] }}</p>
                                    </div>
                                    @if($poliza['uuid'])
                                        <div class="lg:col-span-3">
                                            <span class="text-xs font-semibold text-gray-600">UUID</span>
                                            <p class="text-xs font-mono">{{ $poliza['uuid'] }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Comparar con otras pólizas del grupo --}}
                                @php
                                    $esDuplicado = false;
                                    foreach($grupo['polizas'] as $idx => $otraPoliza) {
                                        if ($idx === $index) continue;
                                        if ($poliza['concepto'] === $otraPoliza['concepto']
                                            && $poliza['fecha'] === $otraPoliza['fecha']
                                            && abs($poliza['cargos'] - $otraPoliza['cargos']) < 0.01
                                            && abs($poliza['abonos'] - $otraPoliza['abonos']) < 0.01) {
                                            $esDuplicado = true;
                                            break;
                                        }
                                    }
                                @endphp

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    @if($esDuplicado)
                                        <div class="flex items-center text-red-600">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="font-semibold">⚠ POSIBLE DUPLICADO REAL</span>
                                            <span class="ml-2 text-sm">(Mismo concepto, fecha y montos)</span>
                                        </div>
                                    @else
                                        <div class="flex items-center text-blue-600">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="font-semibold">✓ Parece ser póliza diferente</span>
                                            <span class="ml-2 text-sm">(Mismo folio por error de numeración)</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
