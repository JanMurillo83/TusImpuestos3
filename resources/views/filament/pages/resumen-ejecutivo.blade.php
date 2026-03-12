<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Resumen Ejecutivo Financiero-Comercial</h1>
            <p class="text-sm text-gray-500">Genera un reporte ejecutivo con IA a partir de los dashboards financiero y comercial.</p>
        </div>

        <form wire:submit.prevent="generarResumen" class="rounded-xl border border-gray-200 bg-white p-4">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button color="primary" type="submit">
                    Generar resumen ejecutivo
                </x-filament::button>
            </div>
        </form>

        @if($registro)
            <div class="rounded-xl border border-gray-200 bg-white p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-500">
                    Última generación: {{ $registro->updated_at->format('d/m/Y H:i') }} · Periodo {{ $registro->periodo }}
                </div>
                @if($pdfUrl)
                    <a
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white"
                        href="{{ $pdfUrl }}"
                        target="_blank"
                        rel="noopener"
                    >
                        Exportar PDF
                    </a>
                @endif
            </div>
        @endif

        @if($secciones)
            @foreach($secciones as $titulo => $contenido)
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">{{ $titulo }}</h2>
                    <div class="mt-3 whitespace-pre-wrap text-sm text-gray-700">
                        {{ trim($contenido) ?: 'Sin contenido generado.' }}
                    </div>
                </div>
            @endforeach
        @elseif($reporte)
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Reporte</h2>
                <div class="mt-3 whitespace-pre-wrap text-sm text-gray-700">{{ $reporte }}</div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
