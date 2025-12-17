<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class EstadCXC extends Model
{
    use Sushi;
    public function getRows()
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $auxiliares = Auxiliares::join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->where('auxiliares.team_id',$team_id)->where('a_ejercicio',$ejercicio)
            ->where('codigo','like','105%')
            ->get();
        $resultado = [];
        foreach ($auxiliares as $item) {
            $resultado[] = [
                'clave'=>$item->codigo,
                'cliente'=>$item->cuenta,
                'factura'=>$item->factura,
                'fecha'=> Carbon::create(substr($item->fecha,0,10)),
                'cargos'=>$item->cargo,
                'abonos'=>$item->abono,
            ];
        }
        return $resultado;
    }
}
