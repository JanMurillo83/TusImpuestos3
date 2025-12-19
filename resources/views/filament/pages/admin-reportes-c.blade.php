<x-filament-panels::page>
    <style>
        .contenedor{
            display: grid;
            grid-template-columns: 30% 70%;
            grid-gap: 10px;
        }
    </style>
    <div class="contenedor">
        <div>
            {{$this->ReporteForm}}
        </div>
        <div>
            {{$this->PreviewForm}}
        </div>
    </div>
</x-filament-panels::page>
