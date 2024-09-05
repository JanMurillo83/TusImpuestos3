<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Prestamo
 *
 * @property int $id
 * @property string $tercero
 * @property Carbon $fecha
 * @property string $tipo
 * @property float $importe
 * @property float|null $reembolso
 * @property string|null $cuenta
 * @property string $tax_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Prestamos extends Model
{
	protected $table = 'prestamos';

	protected $casts = [
		'fecha' => 'datetime',
		'importe' => 'float',
		'reembolso' => 'float'
	];

	protected $fillable = [
		'tercero',
		'fecha',
		'tipo',
		'importe',
		'reembolso',
		'cuenta',
		'tax_id',
        'team_id'
	];
}
