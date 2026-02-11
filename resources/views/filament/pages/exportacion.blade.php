<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Formulario -->
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        <!-- Información adicional -->
        <x-filament::card>
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                    <h3 class="text-lg font-semibold">Sobre las exportaciones</h3>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <p>
                        <strong>Formato del archivo:</strong> El archivo Excel generado incluye:
                    </p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>Encabezado de cada póliza (Folio, Tipo, Fecha, RFC, Concepto)</li>
                        <li>Listado de auxiliares debajo de cada póliza</li>
                        <li>Totales de Cargos y Abonos por póliza</li>
                        <li>Formato profesional con colores y bordes</li>
                    </ul>

                    <p class="mt-4">
                        <strong>Datos incluidos por auxiliar:</strong>
                    </p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>Código de cuenta</li>
                        <li>Nombre de la cuenta</li>
                        <li>RFC (si aplica)</li>
                        <li>Tipo de tercero</li>
                        <li>Cargo</li>
                        <li>Abono</li>
                        <li>Concepto del movimiento</li>
                    </ul>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
