<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cuentasxpagar
 *
 * @property int $id
 * @property string $rfc
 * @property string $nombre
 * @property string $factura
 * @property Carbon $fecha
 * @property Carbon $registro
 * @property float $importe
 * @property float $pagado
 * @property float $saldo
 * @property string $uuid
 * @property string $tax_id
 * @property string|null $espagado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Cuentasxpagars extends Model
{
	protected $table = 'cuentasxpagars';

	protected $casts = [
		'fecha' => 'datetime',
		'registro' => 'datetime',
		'importe' => 'float',
		'pagado' => 'float',
		'saldo' => 'float'
	];

	protected $fillable = [
		'rfc',
		'nombre',
		'factura',
		'fecha',
		'registro',
		'importe',
		'pagado',
		'saldo',
		'uuid',
		'tax_id',
		'espagado',
        'team_id'
	];
}
