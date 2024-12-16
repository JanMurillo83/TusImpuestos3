<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Activosfijo
 *
 * @property int $id
 * @property string|null $clave
 * @property string|null $descripcion
 * @property string|null $marca
 * @property string|null $modelo
 * @property string|null $serie
 * @property string|null $proveedor
 * @property float $importe
 * @property float $depre
 * @property float $acumulado
 * @property string|null $cuentadep
 * @property string|null $cuentaact
 * @property string $tax_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Activosfijos extends Model
{
	protected $table = 'activosfijos';

	protected $casts = [
		'importe' => 'float',
		'depre' => 'float',
		'acumulado' => 'float'
	];

	protected $fillable = [
		'clave',
		'descripcion',
		'marca',
		'modelo',
		'serie',
		'proveedor',
		'importe',
		'depre',
		'acumulado',
		'cuentadep',
		'cuentaact',
		'team_id'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
