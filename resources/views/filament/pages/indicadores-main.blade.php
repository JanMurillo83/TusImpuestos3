<?php
    use Filament\Facades\Filament;
    use App\Http\Controllers\ReportesController;
    use App\Livewire\MainVentasWidget;
    $ejercicio = Filament::getTenant()->ejercicio;
    $periodo = Filament::getTenant()->periodo;
    $team_id = Filament::getTenant()->id;
    (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
?>
<x-filament-panels::page>
</x-filament-panels::page>
