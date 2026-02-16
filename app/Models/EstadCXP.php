<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Sushi\Sushi;

class EstadCXP extends Model
{
    use Sushi;

    public function getRows()
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;

        // Implementar caché estratégico con TTL de 5 minutos
        $cache_key = "estado_cxp:{$team_id}:{$ejercicio}:{$periodo}";

        return Cache::remember($cache_key, 300, function() use ($team_id) {
            // Traer TODOS los auxiliares históricos de CxP sin filtrar por ejercicio
            // para mostrar facturas pendientes de ejercicios anteriores
            // CORREGIDO: Solo cuentas 201% (Proveedores/Acreedores), excluyendo otras cuentas de pasivo
            $auxiliares = Auxiliares::join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
                ->where('auxiliares.team_id',$team_id)
                ->where('codigo','like','201%')
                ->get();

            $resultado = [];
            foreach ($auxiliares as $item) {
                $resultado[] = [
                    'clave'=>$item->codigo,
                    'cliente'=>$item->cuenta,
                    'factura'=>$item->factura,
                    'fecha'=> Carbon::create(substr($item->fecha,0,10)),
                    'cargos'=>$item->abono,
                    'abonos'=>$item->cargo,
                ];
            }
            return $resultado;
        });
    }
}
