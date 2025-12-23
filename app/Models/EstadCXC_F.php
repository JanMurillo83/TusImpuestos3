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
                $fecha = Carbon::create(EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->first()->fecha)->format('Y-m-d');
                $vencimiento = Carbon::create($fecha)->addDays(30);
                $factura_i = EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->get();
                $fac_saldo = $factura_i->sum('cargos')-$factura_i->sum('abonos');
                if($fac_saldo > 0) {
                    //dd($factura_i);
                    $facturas[] = [
                        'factura' => $fac->factura,
                        'fecha' => $fecha,
                        'vencimiento' => $vencimiento,
                        'importe' => $factura_i->sum('cargos'),
                        'pagos' => $factura_i->sum('abonos'),
                        'saldo' => $fac_saldo,
                        'uuid'=> EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->first()->uuid
                    ];
                    $saldo_cliente += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                    if ($vencimiento < $fecha_corte) {
                        $saldo_vencido += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                    } else {
                        $saldo_corriente += $factura_i->sum('cargos') - $factura_i->sum('abonos');
                    }
                }
            }
            $claves[] = ['clave'=>$data->clave,'cliente'=>$cliente?->nombre??'No encontrado','saldo'=>$saldo_cliente,'vencido'=>$saldo_vencido,'corriente'=>$saldo_corriente,'facturas'=>json_encode($facturas)];
        }
        return $claves;
    }


}
