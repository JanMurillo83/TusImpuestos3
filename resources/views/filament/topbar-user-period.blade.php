@php
    $user = auth()->user();
    $tenant = \Filament\Facades\Filament::getTenant();
    $periodo = $tenant?->periodo;
    $ejercicio = $tenant?->ejercicio;
    $periodoLabel = ($periodo && $ejercicio)
        ? sprintf('%02d/%s', $periodo, $ejercicio)
        : 'N/A';
    $userLabel = $user?->name ?: ($user?->email ?? 'N/A');
@endphp

<div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
    <span>Usuario: {{ $userLabel }}</span>
    <span class="text-gray-400">|</span>
    <span>Periodo: {{ $periodoLabel }}</span>
</div>
