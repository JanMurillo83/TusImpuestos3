<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Estadísticas actuales --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::card>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total Auxiliares
                    </div>
                    <div class="text-2xl font-bold">
                        {{ number_format($stats['total_auxiliares'] ?? 0) }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Cuentas en Saldos
                    </div>
                    <div class="text-2xl font-bold">
                        {{ number_format($stats['total_saldos'] ?? 0) }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Ejercicios
                    </div>
                    <div class="text-2xl font-bold">
                        {{ $stats['ejercicios'] ?? 0 }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Periodos (Actual)
                    </div>
                    <div class="text-2xl font-bold">
                        {{ $stats['periodos'] ?? 0 }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Última Actualización
                    </div>
                    <div class="text-sm font-medium">
                        @php
                            $ultimaActualizacion = $stats['ultima_actualizacion'] ?? null;
                        @endphp
                        @if($ultimaActualizacion)
                            {{ \Illuminate\Support\Carbon::parse($ultimaActualizacion)->diffForHumans() }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Alerta informativa --}}
        <x-filament::section>
            <x-slot name="heading">
                ℹ️ Información Importante
            </x-slot>

            <div class="space-y-2 text-sm">
                <p>
                    Esta herramienta permite <strong>recalcular los saldos contables</strong> desde cero a partir de los movimientos registrados en auxiliares.
                </p>
                <p class="font-semibold text-warning-600 dark:text-warning-400">
                    ⚠️ Este proceso puede tomar varios minutos dependiendo del volumen de datos.
                </p>
                <ul class="ml-6 list-disc space-y-1">
                    <li>Si no seleccionas ejercicio ni periodo, se recontabilizarán <strong>TODOS</strong> los periodos.</li>
                    <li>Los saldos existentes se eliminarán y se recalcularán desde auxiliares.</li>
                    <li>Se recomienda ejecutar este proceso fuera de horarios pico.</li>
                    <li>Se generará un log detallado del proceso en <code>storage/logs/laravel.log</code></li>
                </ul>
            </div>
        </x-filament::section>

        {{-- Formulario --}}
        <form wire:submit.prevent="recontabilizar">
            {{ $this->form }}

            <div class="flex gap-3 mt-6">
                <x-filament::button
                    type="submit"
                    color="primary"
                    icon="heroicon-o-arrow-path"
                    wire:loading.attr="disabled"
                    wire:target="recontabilizar"
                >
                    <span wire:loading.remove wire:target="recontabilizar">
                        Recontabilizar Saldos
                    </span>
                    <span wire:loading wire:target="recontabilizar">
                        Procesando...
                    </span>
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-o-trash"
                    wire:click="limpiarCache"
                    wire:loading.attr="disabled"
                    wire:target="limpiarCache"
                >
                    Limpiar Cache
                </x-filament::button>
            </div>
        </form>

        {{-- Spinner de carga --}}
        <div wire:loading wire:target="recontabilizar" class="flex items-center justify-center p-8">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="text-lg font-medium">
                    Recontabilizando saldos...
                </div>
            </div>
        </div>

        {{-- Informe detallado --}}
        @if($ultimoInforme)
            <x-filament::section>
                <x-slot name="heading">
                    📊 Informe de Recontabilización
                </x-slot>

                <div class="space-y-4">
                    {{-- Resumen --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-success-50 dark:bg-success-900/20 p-4 rounded-lg">
                            <div class="text-sm font-medium text-success-700 dark:text-success-400">Periodos Procesados</div>
                            <div class="text-2xl font-bold">{{ $ultimoInforme['resumen']['total_periodos'] }}</div>
                        </div>
                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
                            <div class="text-sm font-medium text-primary-700 dark:text-primary-400">Cuentas Actualizadas</div>
                            <div class="text-2xl font-bold">{{ $ultimoInforme['resumen']['total_cuentas'] }}</div>
                        </div>
                        @if($ultimoInforme['resumen']['total_errores'] > 0)
                            <div class="bg-danger-50 dark:bg-danger-900/20 p-4 rounded-lg">
                                <div class="text-sm font-medium text-danger-700 dark:text-danger-400">Errores</div>
                                <div class="text-2xl font-bold">{{ $ultimoInforme['resumen']['total_errores'] }}</div>
                            </div>
                        @endif
                        @if($ultimoInforme['resumen']['total_inconsistencias'] > 0)
                            <div class="bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg">
                                <div class="text-sm font-medium text-warning-700 dark:text-warning-400">Inconsistencias</div>
                                <div class="text-2xl font-bold">{{ $ultimoInforme['resumen']['total_inconsistencias'] }}</div>
                            </div>
                        @endif
                        <div class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-400">Duración</div>
                            <div class="text-2xl font-bold">{{ $ultimoInforme['duracion_segundos'] }}s</div>
                        </div>
                    </div>

                    {{-- Cuentas por periodo --}}
                    @if(count($ultimoInforme['cuentas_por_periodo']) > 0)
                        <div>
                            <h4 class="font-semibold mb-2">Cuentas por Periodo:</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($ultimoInforme['cuentas_por_periodo'] as $periodo => $cantidad)
                                    <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded text-center">
                                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $periodo }}</div>
                                        <div class="font-semibold">{{ $cantidad }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Errores --}}
                    @if(count($ultimoInforme['errores_detalle']) > 0)
                        <div>
                            <h4 class="font-semibold text-danger-600 dark:text-danger-400 mb-2">
                                ⚠️ Errores Detectados ({{ count($ultimoInforme['errores_detalle']) }}):
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach($ultimoInforme['errores_detalle'] as $error)
                                    <div class="bg-danger-50 dark:bg-danger-900/20 p-3 rounded text-sm">
                                        <div class="font-medium">
                                            @if($error['tipo'] === 'cuenta')
                                                Cuenta: {{ $error['cuenta'] }} - {{ $error['ejercicio'] }}/{{ str_pad($error['periodo'], 2, '0', STR_PAD_LEFT) }}
                                            @else
                                                Periodo: {{ $error['ejercicio'] }}/{{ str_pad($error['periodo'], 2, '0', STR_PAD_LEFT) }}
                                            @endif
                                        </div>
                                        <div class="text-danger-700 dark:text-danger-300">{{ $error['error'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Inconsistencias --}}
                    @if(count($ultimoInforme['inconsistencias']) > 0)
                        <div>
                            <h4 class="font-semibold text-warning-600 dark:text-warning-400 mb-2">
                                ⚠️ Inconsistencias Detectadas ({{ count($ultimoInforme['inconsistencias']) }}):
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach($ultimoInforme['inconsistencias'] as $incons)
                                    <div class="bg-warning-50 dark:bg-warning-900/20 p-3 rounded text-sm">
                                        <div class="font-medium">Cuenta: {{ $incons->codigo }}</div>
                                        <div class="grid grid-cols-2 gap-2 mt-1">
                                            <div>Saldo en reportes: {{ number_format($incons->final, 2) }}</div>
                                            <div>Saldo real: {{ number_format($incons->cargos_reales - $incons->abonos_reales, 2) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Cuentas sin catálogo --}}
                    @if(count($ultimoInforme['cuentas_sin_catalogo']) > 0)
                        <div>
                            <h4 class="font-semibold text-info-600 dark:text-info-400 mb-2">
                                ℹ️ Cuentas Sin Catálogo ({{ count($ultimoInforme['cuentas_sin_catalogo']) }}):
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                                @foreach($ultimoInforme['cuentas_sin_catalogo'] as $cuenta)
                                    <div class="bg-info-50 dark:bg-info-900/20 p-2 rounded text-sm">
                                        {{ $cuenta->codigo }} - ${{ number_format($cuenta->final, 2) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Cuentas sin movimientos --}}
                    @if(count($ultimoInforme['cuentas_sin_movimientos']) > 0)
                        <div>
                            <h4 class="font-semibold text-warning-600 dark:text-warning-400 mb-2">
                                ⚠️ Cuentas Con Saldo Pero Sin Movimientos ({{ count($ultimoInforme['cuentas_sin_movimientos']) }}):
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                                @foreach($ultimoInforme['cuentas_sin_movimientos'] as $cuenta)
                                    <div class="bg-warning-50 dark:bg-warning-900/20 p-2 rounded text-sm">
                                        {{ $cuenta->codigo }} - ${{ number_format($cuenta->final, 2) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Notas adicionales --}}
        <x-filament::section
            collapsible
            collapsed
        >
            <x-slot name="heading">
                📖 Casos de Uso
            </x-slot>

            <div class="space-y-3 text-sm">
                <div>
                    <h4 class="font-semibold">1. Recontabilizar todo el sistema</h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        Deja ambos campos vacíos y haz clic en "Recontabilizar Saldos". Esto recalculará todos los ejercicios y periodos.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">2. Recontabilizar un ejercicio completo</h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        Selecciona el ejercicio y deja el periodo vacío. Se recontabilizarán todos los periodos de ese ejercicio.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">3. Recontabilizar un periodo específico</h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        Selecciona tanto el ejercicio como el periodo. Solo se recontabilizará ese periodo específico.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">4. Limpiar solo el cache</h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        Si solo necesitas refrescar el cache sin recontabilizar, usa el botón "Limpiar Cache".
                    </p>
                </div>

                <div class="mt-4 p-3 bg-info-50 dark:bg-info-900/20 rounded-lg">
                    <p class="font-semibold text-info-700 dark:text-info-400">
                        💡 Tip: Ejecuta primero con un solo periodo para verificar que todo funciona correctamente antes de recontabilizar todo el sistema.
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
