<?php
use Filament\Facades\Filament;
?>
<footer class="fixed bottom-0 left-0 z-20 w-full p-1 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600" style="margin-top: 2rem !important;">
    <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">Usuario Actual: <b>{{auth()->user()->name ?? ''}}</b>   Periodo Actual: <b>{{Filament::getTenant()->periodo ?? ''}}</b>    © 2024 Tus-Impuestos. Derechos Reservados.
    </span>
</footer>
