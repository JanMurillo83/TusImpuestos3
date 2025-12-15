<?php

namespace App\Models;

use App\Http\Controllers\MainChartsController;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class AuxVentasEjercicio extends Model
{
    use Sushi;
    public function getRows()
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $resultado = app(MainChartsController::class)->GetAuxiliaresEjercicio($team_id, '40100000', $periodo, $ejercicio);
        $auxiliares = [];
        foreach ($resultado as $item) {
            $auxiliares[] = [
                'id'=>(int)$item->id,
                'codigo'=>(string)$item->codigo,
                'cuenta'=>(string)$item->cuenta,
                'concepto'=>(string)$item->concepto,
                'cargo'=>(float)$item->cargo,
                'abono'=>(float)$item->abono,
                'factura'=>(string)$item->factura,
                'nopartida'=>(string)$item->nopartida,
                'cat_polizas_id'=>(int)$item->cat_polizas_id,
                'uuid'=>(string)$item->uuid,
                'team_id'=>(int)$item->team_id,
                'created_at'=>(string)$item->created_at,
                'updated_at'=>(string)$item->updated_at,
                'a_ejercicio'=>(int)$item->a_ejercicio,
                'a_periodo'=>(int)$item->a_periodo,
                'igeg_id'=>(int)$item->igeg_id,
                'tipo'=>(string)$item->tipo,
                'folio'=>(string)$item->folio,
                'fecha'=>(string)$item->fecha,
                'cargos'=>(float)$item->cargos,
                'abonos'=>(float)$item->abonos,
                'periodo'=>(int)$item->periodo,
                'ejercicio'=>(int)$item->ejercicio,
                'referencia'=>(string)$item->referencia,
                'tiposat'=>(string)$item->tiposat,
                'idcfdi'=>(int)$item->idcfdi,
                'idmovb'=>(int)$item->idmovb
            ];
        }
        return $auxiliares;
    }
}
