<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class EstadCXP_F_F extends Model
{
    use Sushi;
    protected static $cliente;
    public static function getCliente($cliente)
    {
        self::$cliente = $cliente;
        return new static(); // Return a new instance for chaining
    }

    public function getRows() : array
    {
        return EstadCXP_F::where('clave',self::$cliente)->first()->facturas;
    }
}
