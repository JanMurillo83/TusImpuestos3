<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner informativo -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Reportes Financieros Conforme a NIF
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Genera los estados financieros básicos de acuerdo a las <strong>Normas de Información Financiera (NIF)</strong> vigentes en México.
                        Estos reportes cumplen con los estándares del CINIF (Consejo Mexicano de Normas de Información Financiera).
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-full font-semibold">1</span>
                            <span><strong>NIF B-6:</strong> Balance General (Situación Financiera)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full font-semibold">2</span>
                            <span><strong>NIF B-3:</strong> Estado de Resultados Integral</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 rounded-full font-semibold">3</span>
                            <span><strong>NIF B-4:</strong> Cambios en el Capital Contable</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 rounded-full font-semibold">4</span>
                            <span><strong>NIF B-2:</strong> Estado de Flujos de Efectivo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario con acciones -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
