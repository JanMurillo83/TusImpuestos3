<?php

namespace App\Models;

use Carbon\Carbon;
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
        foreach ($datos as $data)
        {
            $cliente = CatCuentas::where('codigo',$data->clave)->first();
            $facturas = [];
            $fac_data = EstadCXC::where('clave',$data->clave)->select('factura')->distinct()->get();
            $saldo_cliente = 0;
            foreach ($fac_data as $fac)
            {
                $fecha = Carbon::create(EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->first()->fecha)->format('Y-m-d');
                $vencimiento = Carbon::create($fecha)->addDays(30);
                $factura_i = EstadCXC::where('clave',$data->clave)->where('factura',$fac->factura)->get();
                $facturas[] = [
                    'factura'=>$fac->factura,
                    'fecha'=>$fecha,
                    'vencimiento'=>$vencimiento,
                    'importe'=>$factura_i->sum('cargos'),
                    'pagos'=>$factura_i->sum('abonos'),
                    'saldo'=>$factura_i->sum('cargos')-$factura_i->sum('abonos')
                ];
                $saldo_cliente += $factura_i->sum('cargos')-$factura_i->sum('abonos');
            }
            $claves[] = ['clave'=>$data->clave,'cliente'=>$cliente->nombre,'saldo'=>$saldo_cliente,'facturas'=>json_encode($facturas)];
        }
        return $claves;
    }


}
