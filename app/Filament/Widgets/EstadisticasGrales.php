<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EstadisticasGrales extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('CFDI\'s pendientes de contabilizar en el Periodo', function(){
                $cont = count(DB::table('almacencfdis')
                ->where('used','NO')
                ->where('team_id',Filament::getTenant()->id)
                ->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio)
                ->get());
                return $cont;
            }),
        ];
    }
}
