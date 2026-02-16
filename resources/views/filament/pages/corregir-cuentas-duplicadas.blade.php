<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Alerta informativa --}}
        <x-filament::section>
            <x-slot name="heading">
                ⚠️ Cuentas Duplicadas Detectadas
            </x-slot>

            <div class="space-y-2 text-sm">
                <p>
                    Esta herramienta detecta cuentas con el <strong>mismo código pero diferentes nombres</strong> en el catálogo de cuentas.
                </p>
                <p class="text-warning-600 dark:text-warning-400 font-semibold">
                    Las cuentas duplicadas pueden causar descuadres en la balanza de comprobación.
                </p>
                <div class="mt-4 p-3 bg-info-50 dark:bg-info-900/20 rounded-lg">
                    <h4 class="font-semibold mb-2">¿Cómo funciona la corrección?</h4>
                    <ol class="list-decimal ml-5 space-y-1">
                        <li>Se mantiene la <strong>primera cuenta</strong> registrada (más antigua)</li>
                        <li>Las cuentas secundarias se reasignan al siguiente código disponible con el mismo prefijo</li>
                        <li>Se actualizan los <strong>auxiliares</strong> que coincidan con código Y nombre</li>
                        <li>Se actualizan <strong>saldos_reportes</strong> y <strong>saldoscuentas</strong></li>
                        <li>Se genera un reporte de los cambios realizados</li>
                    </ol>
                </div>
            </div>
        </x-filament::section>

        {{-- Botón para detectar duplicadas --}}
        <div class="flex gap-3">
            <x-filament::button
                icon="heroicon-o-magnifying-glass"
                wire:click="detectarDuplicadas"
                wire:loading.attr="disabled"
            >
                Buscar Duplicadas
            </x-filament::button>

            @if(count($duplicadas) > 0)
                <x-filament::button
                    color="warning"
                    icon="heroicon-o-wrench-screwdriver"
                    wire:click="corregirTodas"
                    wire:loading.attr="disabled"
                    wire:confirm="¿Está seguro de corregir TODAS las cuentas duplicadas? Esta acción no se puede deshacer."
                >
                    Corregir Todas
                </x-filament::button>
            @endif
        </div>

        {{-- Lista de duplicadas --}}
        @if(count($duplicadas) > 0)
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">
                    Cuentas Duplicadas Encontradas: {{ count($duplicadas) }}
                </h3>

                @foreach($duplicadas as $duplicada)
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <span class="font-mono text-lg">{{ $duplicada['codigo'] }}</span>
                                    <span class="ml-3 text-sm text-gray-500">{{ $duplicada['cantidad'] }} registros duplicados</span>
                                </div>
                                <div>
                                    <x-filament::button
                                        color="warning"
                                        size="sm"
                                        icon="heroicon-o-wrench"
                                        wire:click="corregirDuplicada('{{ $duplicada['codigo'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="¿Está seguro de corregir esta cuenta duplicada?"
                                    >
                                        Corregir
                                    </x-filament::button>
                                </div>
                            </div>
                        </x-slot>

                        <div class="space-y-3">
                            @foreach($duplicada['cuentas'] as $index => $cuenta)
                                <div class="p-4 rounded-lg border
                                    @if($cuenta['es_primera'])
                                        border-success-500 bg-success-50 dark:bg-success-900/20
                                    @else
                                        border-warning-500 bg-warning-50 dark:bg-warning-900/20
                                    @endif">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                @if($cuenta['es_primera'])
                                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-success-600 text-white">
                                                        ✓ SE MANTIENE (Principal)
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-warning-600 text-white">
                                                        ⚠️ SE REASIGNARÁ
                                                    </span>
                                                @endif
                                                <span class="text-xs text-gray-500">ID: {{ $cuenta['id'] }}</span>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="font-semibold">Nombre:</span>
                                                    <span class="ml-2">{{ $cuenta['nombre'] }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-semibold">Naturaleza:</span>
                                                    <span class="ml-2">{{ $cuenta['naturaleza'] }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-semibold">Acumula:</span>
                                                    <span class="ml-2">{{ $cuenta['acumula'] }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-semibold">Auxiliares afectados:</span>
                                                    <span class="ml-2 font-mono">{{ number_format($cuenta['auxiliares_count']) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded text-sm">
                            <p class="font-semibold mb-1">Acción a realizar:</p>
                            <ul class="list-disc ml-5 space-y-1 text-gray-600 dark:text-gray-400">
                                <li>La cuenta <strong>"{{ $duplicada['cuentas'][0]['nombre'] }}"</strong> se mantendrá con el código <strong>{{ $duplicada['codigo'] }}</strong></li>
                                @foreach($duplicada['cuentas'] as $index => $cuenta)
                                    @if(!$cuenta['es_primera'])
                                        <li>La cuenta <strong>"{{ $cuenta['nombre'] }}"</strong> se moverá al siguiente código disponible con prefijo <strong>{{ substr($duplicada['codigo'], 0, 5) }}xxx</strong></li>
                                        <li>Se actualizarán <strong>{{ number_format($cuenta['auxiliares_count']) }} auxiliares</strong> que usen este código Y este nombre</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="text-success-400 text-6xl mb-4">
                        ✓
                    </div>
                    <h3 class="text-lg font-semibold text-success-700 dark:text-success-300 mb-2">
                        No se encontraron cuentas duplicadas
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        El catálogo de cuentas está limpio y sin duplicados.
                    </p>
                </div>
            </x-filament::section>
        @endif

        {{-- Correcciones realizadas --}}
        @if(count($correccionesRealizadas) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    📋 Correcciones Realizadas ({{ count($correccionesRealizadas) }})
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left">Código Original</th>
                                <th class="px-3 py-2 text-left">Código Nuevo</th>
                                <th class="px-3 py-2 text-left">Nombre de Cuenta</th>
                                <th class="px-3 py-2 text-right">Auxiliares Actualizados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($correccionesRealizadas as $corr)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2 font-mono">{{ $corr['codigo_original'] }}</td>
                                    <td class="px-3 py-2 font-mono text-success-600 dark:text-success-400">
                                        {{ $corr['codigo_nuevo'] }}
                                    </td>
                                    <td class="px-3 py-2">{{ $corr['nombre'] }}</td>
                                    <td class="px-3 py-2 text-right font-mono">
                                        {{ number_format($corr['auxiliares_actualizados']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right">TOTAL:</td>
                                <td class="px-3 py-2 text-right font-mono">
                                    {{ number_format(array_sum(array_column($correccionesRealizadas, 'auxiliares_actualizados'))) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Spinner de carga --}}
        <div wire:loading wire:target="corregirDuplicada,corregirTodas" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="text-lg font-medium">
                    Procesando corrección...
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
