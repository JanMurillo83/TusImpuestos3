<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CuentasGasto
 *
 * @property int $id
 * @property int $rubro
 * @property string $cuenta
 * @property string $concepto
 *
 * @package App\Models
 */
class CuentasGastos extends Model
{
	protected $table = 'cuentas_gastos';
	public $timestamps = false;

	protected $casts = [
		'rubro' => 'int'
	];

	protected $fillable = [
		'rubro',
		'cuenta',
		'concepto',
        'team_id'
	];
}
