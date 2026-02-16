<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class EstadCXP_F extends Model
{
    use Sushi;
    protected $casts = [
        'facturas' => 'array',
    ];
    public function getRows()
    {
        $datos = EstadCXP::select('clave')->distinct()->get();
        $claves = [];
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        foreach ($datos as $data)
        {
            $cliente = CatCuentas::where('codigo',$data->clave)->first();
            $facturas = [];
            $fac_data = EstadCXP::where('clave',$data->clave)->select('factura')->distinct()->get();
            $saldo_cliente = 0;
            $saldo_vencido = 0;
            $saldo_corriente = 0;
            $fecha_corte = Carbon::create($ejercicio,$periodo,1)->format('Y-m-d');
            foreach ($fac_data as $fac) {
                if (EstadCXP::where('clave', $data->clave)->where('factura', $fac->factura)->exists()) {
                    $fecha = Carbon::create(EstadCXP::where('clave', $data->clave)->where('factura', $fac->factura)->first()->fecha)->format('Y-m-d');
                    $vencimiento = Carbon::create($fecha)->addDays(30);
                    $factura_i = EstadCXP::where('clave', $data->clave)->where('factura', $fac->factura)->get();
                    $fac_saldo = $factura_i->sum('cargos') - $factura_i->sum('abonos');
                    if ($fac_saldo > 0) {
                        $facturas[] = [
                            'factura' => $fac->factura,
                            'fecha' => $fecha,
                            'vencimiento' => $vencimiento,
                            'importe' => $factura_i->sum('cargos'),
                            'pagos' => $factura_i->sum('abonos'),
                            'saldo' => $factura_i->sum('cargos') - $factura_i->sum('abonos'),
                            'uuid' => EstadCXP::where('clave', $data->clave)->where('factura', $fac->factura)->first()->uuid
                        ];
                        $saldo_cliente += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                        if ($vencimiento < $fecha_corte) {
                            $saldo_vencido += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                        } else {
                            $saldo_corriente += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                        }
                    }
                }
            }

            // IMPORTANTE: Incluir TODOS los movimientos sin relación (cargos y abonos sin factura)
            // Estos movimientos afectan el saldo pero no están ligados a ninguna factura específica
            // Ejemplos: anticipos a proveedores, notas de crédito/débito, pagos sin aplicar, etc.
            $movimientos_sin_relacion = EstadCXP::where('clave', $data->clave)
                ->where(function($q) {
                    $q->whereNull('factura')
                      ->orWhere('factura', '')
                      ->orWhere('factura', 'Sin Relacion');
                })
                ->get();

            // En CXP: Saldo = Cargos - Abonos (nota: en EstadCXP.php líneas 40-41 están invertidos en origen)
            // La lógica aquí debe seguir la misma que en las facturas (línea 36)
            $saldo_sin_relacion = $movimientos_sin_relacion->sum('cargos') - $movimientos_sin_relacion->sum('abonos');
            $saldo_cliente += $saldo_sin_relacion;

            // Los movimientos sin relación se consideran como "corriente" (no vencidos)
            $saldo_corriente += $saldo_sin_relacion;

            $claves[] = ['clave'=>$data->clave,'cliente'=>$cliente->nombre,'saldo'=>$saldo_cliente,'vencido'=>$saldo_vencido,'corriente'=>$saldo_corriente,'facturas'=>json_encode($facturas)];
        }
        return $claves;
    }
}
