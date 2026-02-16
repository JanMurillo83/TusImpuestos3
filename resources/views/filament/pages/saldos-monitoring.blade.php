<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Actions --}}
        <div class="flex justify-end gap-2">
            <x-filament::button wire:click="refreshData" icon="heroicon-o-arrow-path">
                Actualizar
            </x-filament::button>
            <x-filament::button wire:click="runHealthCheck" icon="heroicon-o-wrench" color="info">
                Ejecutar Health Check
            </x-filament::button>
        </div>

        {{-- Quick Metrics Summary --}}
        @if(!empty($dashboardSummary))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Estado General</p>
                        <p class="text-2xl font-bold mt-1">
                            @php
                                $failed = collect($healthChecks)->where('status', 'fail')->count();
                                $warnings = collect($healthChecks)->where('status', 'warning')->count();
                                $status = $failed > 0 ? 'Crítico' : ($warnings > 0 ? 'Advertencia' : 'OK');
                                $color = $failed > 0 ? 'text-red-600' : ($warnings > 0 ? 'text-yellow-600' : 'text-green-600');
                            @endphp
                            <span class="{{ $color }}">{{ $status }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Tiempo Promedio Job</p>
                        <p class="text-2xl font-bold mt-1">
                            {{ number_format($dashboardSummary['job_performance']['avg_duration_ms'] ?? 0, 0) }} ms
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Tasa de Éxito</p>
                        <p class="text-2xl font-bold mt-1">
                            {{ number_format($dashboardSummary['job_performance']['success_rate'] ?? 0, 1) }}%
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Cache Hit Rate</p>
                        <p class="text-2xl font-bold mt-1">
                            {{ number_format($dashboardSummary['cache_stats']['hit_rate'] ?? 0, 1) }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Health Checks Panel --}}
        <x-filament::section>
            <x-slot name="heading">
                Estado del Sistema
            </x-slot>

            <div class="space-y-3">
                @foreach($healthChecks as $check)
                    @php
                        $statusIcon = 'heroicon-o-check-circle';
                        if ($check['status'] === 'warning') {
                            $statusIcon = 'heroicon-o-exclamation-triangle';
                        } elseif ($check['status'] === 'fail') {
                            $statusIcon = 'heroicon-o-x-circle';
                        }

                        $statusColor = 'success';
                        if ($check['status'] === 'warning') {
                            $statusColor = 'warning';
                        } elseif ($check['status'] === 'fail') {
                            $statusColor = 'danger';
                        }

                        $borderClass = 'border-green-200 bg-green-50';
                        $iconClass = 'text-green-600';
                        if ($check['status'] === 'warning') {
                            $borderClass = 'border-yellow-200 bg-yellow-50';
                            $iconClass = 'text-yellow-600';
                        } elseif ($check['status'] === 'fail') {
                            $borderClass = 'border-red-200 bg-red-50';
                            $iconClass = 'text-red-600';
                        }
                    @endphp
                    <div class="flex items-center justify-between p-4 rounded-lg border {{ $borderClass }}">
                        <div class="flex items-center gap-3">
                            <x-filament::icon icon="{{ $statusIcon }}" class="w-6 h-6 {{ $iconClass }}" />
                            <div>
                                <div class="font-semibold text-gray-900">{{ $check['check'] }}</div>
                                <div class="text-sm text-gray-600">{{ $check['message'] }}</div>
                            </div>
                        </div>
                        @if(isset($check['details']) && !empty($check['details']))
                            <x-filament::badge color="{{ $statusColor }}">
                                {{ count($check['details']) }} detalles
                            </x-filament::badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Recent Alerts --}}
        <x-filament::section>
            <x-slot name="heading">
                Alertas Recientes
            </x-slot>

            @if(empty($recentAlerts))
                <div class="text-center text-gray-500 py-8">
                    No hay alertas recientes
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentAlerts as $alert)
                        @php
                            $alertColor = 'gray';
                            if ($alert->severity === 'critical') {
                                $alertColor = 'danger';
                            } elseif ($alert->severity === 'warning') {
                                $alertColor = 'warning';
                            } elseif ($alert->severity === 'info') {
                                $alertColor = 'info';
                            }
                        @endphp
                        <div class="flex items-start justify-between p-4 rounded-lg border border-gray-200 @if($alert->resolved_at) opacity-50 @endif">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-filament::badge color="{{ $alertColor }}">
                                        {{ ucfirst($alert->severity) }}
                                    </x-filament::badge>
                                    <span class="text-sm text-gray-500">{{ $alert->alert_type }}</span>
                                </div>
                                <div class="font-semibold text-gray-900">{{ $alert->title }}</div>
                                <div class="text-sm text-gray-600">{{ $alert->message }}</div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ \Illuminate\Support\Carbon::parse($alert->created_at)->diffForHumans() }}
                                </div>
                            </div>
                            @if(!$alert->resolved_at)
                                <x-filament::button
                                    wire:click="resolveAlert({{ $alert->id }})"
                                    size="sm"
                                    color="success"
                                >
                                    Resolver
                                </x-filament::button>
                            @else
                                <x-filament::badge color="success">
                                    Resuelta
                                </x-filament::badge>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Audit Log --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Registro de Auditoría (Últimas 20 entradas)
            </x-slot>

            @if(empty($recentAuditLog))
                <div class="text-center text-gray-500 py-8">
                    No hay entradas en el registro de auditoría
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-left">Cuenta</th>
                                <th class="px-4 py-2 text-left">Campo</th>
                                <th class="px-4 py-2 text-right">Valor Anterior</th>
                                <th class="px-4 py-2 text-right">Valor Nuevo</th>
                                <th class="px-4 py-2 text-left">Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAuditLog as $log)
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-gray-600">
                                        {{ \Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-4 py-2 font-mono">{{ $log->codigo }}</td>
                                    <td class="px-4 py-2">{{ $log->field_changed }}</td>
                                    <td class="px-4 py-2 text-right font-mono">{{ number_format($log->old_value, 2) }}</td>
                                    <td class="px-4 py-2 text-right font-mono">{{ number_format($log->new_value, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $log->user_id ?? 'Sistema' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Job History --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Historial de Jobs (Últimos 50)
            </x-slot>

            @if(empty($jobHistory))
                <div class="text-center text-gray-500 py-8">
                    No hay historial de jobs
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-left">Job ID</th>
                                <th class="px-4 py-2 text-left">Cuenta</th>
                                <th class="px-4 py-2 text-left">Estado</th>
                                <th class="px-4 py-2 text-right">Duración</th>
                                <th class="px-4 py-2 text-left">Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobHistory as $job)
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-gray-600">
                                        {{ \Illuminate\Support\Carbon::parse($job->created_at)->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-4 py-2 font-mono text-xs">{{ \Illuminate\Support\Str::limit($job->job_id, 20) }}</td>
                                    <td class="px-4 py-2 font-mono">{{ $job->codigo }}</td>
                                    <td class="px-4 py-2">
                                        @if($job->status === 'completed')
                                            <x-filament::badge color="success">Completado</x-filament::badge>
                                        @elseif($job->status === 'failed')
                                            <x-filament::badge color="danger">Fallido</x-filament::badge>
                                        @else
                                            <x-filament::badge color="warning">{{ $job->status }}</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right font-mono">
                                        @if($job->duration_ms)
                                            {{ number_format($job->duration_ms, 0) }} ms
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">
                                        {{ \Illuminate\Support\Str::limit($job->error_message ?? 'OK', 50) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
