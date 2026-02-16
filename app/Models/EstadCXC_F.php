<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class EstadCXC_F extends Model
{
    use Sushi;
    protected $casts = [
        'facturas' => 'array',
    ];
    public function getRows()
    {
        $datos = EstadCXC::select('clave')->distinct()->get();
        $claves = [];
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        foreach ($datos as $data)
        {
            $cliente = CatCuentas::where('codigo',$data->clave)->first();
            $facturas = [];
            $fac_data = EstadCXC::where('clave',$data->clave)->select('factura')->distinct()->get();
            $saldo_cliente = 0;
            $saldo_vencido = 0;
            $saldo_corriente = 0;
            $fecha_corte = Carbon::create($ejercicio,$periodo,1)->format('Y-m-d');
            foreach ($fac_data as $fac)
            {
                if(EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->exists()) {
                    $fecha = Carbon::create(EstadCXC::where('clave', $data->clave)->where('factura', $fac->factura)->first()->fecha)->format('Y-m-d');
                    $vencimiento = Carbon::create($fecha)->addDays(30);
                    $factura_i = EstadCXC::where('clave', $data->clave)->where('factura', $fac->factura)->get();
                    $fac_saldo = $factura_i->sum('cargos') - $factura_i->sum('abonos');
                    if ($fac_saldo > 0) {
                        //dd($factura_i);
                        $facturas[] = [
                            'factura' => $fac->factura,
                            'fecha' => $fecha,
                            'vencimiento' => $vencimiento,
                            'importe' => $factura_i->sum('cargos'),
                            'pagos' => $factura_i->sum('abonos'),
                            'saldo' => $fac_saldo,
                            'uuid' => EstadCXC::where('clave', $data->clave)->where('factura', $fac->factura)->first()->uuid
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
            // Ejemplos: anticipos, notas de crédito/débito, pagos sin aplicar, etc.
            $movimientos_sin_relacion = EstadCXC::where('clave', $data->clave)
                ->where(function($q) {
                    $q->whereNull('factura')
                      ->orWhere('factura', '')
                      ->orWhere('factura', 'Sin Relacion');
                })
                ->get();

            // En CXC: Saldo = Cargos - Abonos
            // Cargos sin relación AUMENTAN el saldo, Abonos sin relación DISMINUYEN el saldo
            $saldo_sin_relacion = $movimientos_sin_relacion->sum('cargos') - $movimientos_sin_relacion->sum('abonos');
            $saldo_cliente += $saldo_sin_relacion;

            // Los movimientos sin relación se consideran como "corriente" (no vencidos)
            $saldo_corriente += $saldo_sin_relacion;

            $claves[] = ['clave'=>$data->clave,'cliente'=>$cliente?->nombre??'No encontrado','saldo'=>$saldo_cliente,'vencido'=>$saldo_vencido,'corriente'=>$saldo_corriente,'facturas'=>json_encode($facturas)];
        }
        return $claves;
    }


}
