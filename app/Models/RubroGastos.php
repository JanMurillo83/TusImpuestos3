<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RubroGasto
 *
 * @property int $id
 * @property int $rubro
 * @property string $nombre
 * @property string $mayor
 *
 * @package App\Models
 */
class RubroGastos extends Model
{
	protected $table = 'rubro_gastos';
	public $timestamps = false;

	protected $casts = [
		'rubro' => 'int'
	];

	protected $fillable = [
		'rubro',
		'nombre',
		'mayor',
        'team_id'
	];
}
