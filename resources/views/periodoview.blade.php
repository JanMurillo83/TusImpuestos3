<?php
    use Filament\Facades\Filament;
    $periodo = Filament::getTenant()->periodo;
    $ejercicio = Filament::getTenant()->ejercicio;
?>
<div class="flex flex-col items-center justify-center">
    <div class="!z-5 relative flex h-full w-full flex-col rounded-xl bg-white bg-clip-border p-4 shadow-3xl shadow-shadow-500 dark:!bg-navy-800 dark:text-white dark:shadow-none">
        <div class="flex flex-col">
            <div class="flex justify-between">
                <p class="text-sm font-medium text-gray-600">Perido: {{Filament::getTenant()->periodo}}</p>
            </div>
            <div class="flex justify-between">
                <p class="text-sm font-medium text-gray-600">Ejercicio: {{Filament::getTenant()->ejercicio}}</p>
            </div>
        </div>
    </div>
</div>
