<x-filament-panels::page>
<style>
    .container-fluid {width: 100%; padding-right: 5px; padding-left: 5px; margin-right: auto; margin-left: auto;}
    .row {display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; page-break-inside: avoid;}
    .col-1 {flex: 0 0 8.333333%; max-width: 8.333333%; padding: 0 5px; box-sizing: border-box;}
    .col-2 {flex: 0 0 16.666667%; max-width: 16.666667%; padding: 0 5px; box-sizing: border-box;}
    .col-3 {flex: 0 0 25%; max-width: 25%; padding: 0 5px; box-sizing: border-box;}
    .col-4 {flex: 0 0 33.333333%; max-width: 33.333333%; padding: 0 5px; box-sizing: border-box;}
    .col-8 {flex: 0 0 66.666667%; max-width: 66.666667%; padding: 0 5px; box-sizing: border-box;}
</style>
    <div class="container-fluid">
        <div class="row">
            <div class="col-3">@livewire(\App\Livewire\GraficasWidget::class)</div>
            <div class="col-3" style="width: 100% !important;">@livewire(\App\Livewire\IndicadoresWidget::class)</div>
            <div class="col-3" style="width: 100% !important;">@livewire(\App\Livewire\Indicadores2Widget::class)</div>
            <div class="col-3" style="width: 100% !important;">@livewire(\App\Filament\Widgets\EstadisticasGrales::class)</div>
        </div>
    </div>
</x-filament-panels::page>
