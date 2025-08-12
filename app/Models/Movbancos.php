<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Movbanco
 *
 * @property int $id
 * @property Carbon $fecha
 * @property string $tax_id
 * @property string $tipo
 * @property string $tercero
 * @property string $cuenta
 * @property string $factura
 * @property string $uuid
 * @property float $importe
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $concepto
 * @property string $contabilizada
 * @property string|null $movbancoscol
 *
 * @package App\Models
 */
class Movbancos extends Model
{
	protected $table = 'movbancos';

	protected $casts = [
		'fecha' => 'datetime',
		'importe' => 'float'
	];

	protected $fillable = [
		'fecha',
		'tax_id',
		'tipo',
		'tercero',
		'cuenta',
		'factura',
		'uuid',
		'importe',
		'concepto',
		'contabilizada',
		'movbancoscol',
        'ejercicio',
        'periodo',
        'moneda',
        'tcambio',
        'pendiente_apli',
        'team_id'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function factura():BelongsTo
    {
        return $this->belongsTo(IngresosEgresos::class);
    }

    public function facturas():BelongsToMany
    {
        return $this->belongsToMany(IngresosEgresos::class);
    }
}
