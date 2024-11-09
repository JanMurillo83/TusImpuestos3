<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UnidProd
 * 
 * @property int $id
 * @property string $unidad
 * @property string $descripcion
 * @property string $unidad_sat
 * @property string $atributo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UnidProd extends Model
{
	protected $table = 'unid_prods';

	protected $fillable = [
		'unidad',
		'descripcion',
		'unidad_sat',
		'atributo'
	];
}
