<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\DescargaSAT;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EstadisticasGrales extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }
    protected function getStats(): array
    {
        return [
            Stat::make('CFDI\'s pendientes de contabilizar en el Periodo', function(){
                if(count(DB::table('historico_tcs')->where('fecha',Carbon::now())->get()) == 0) {
                    $tip_cam = app(DescargaSAT::class)->TipoDeCambioBMX();
                    if ($tip_cam->getStatusCode() === 200) {
                        $vals = json_decode($tip_cam->getBody()->getContents());
                        $tc_n = floatval($vals->bmx->series[0]->datos[0]->dato);
                        DB::table('historico_tcs')->insert([
                            'fecha' => Carbon::now(),
                            'tipo_cambio' => $tc_n,
                            'team_id' => Filament::getTenant()->id
                        ]);
                    } else {
                        DB::table('historico_tcs')->insert([
                            'fecha' => Carbon::now(),
                            'tipo_cambio' => 1,
                            'team_id' => Filament::getTenant()->id
                        ]);
                    }
                }
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
