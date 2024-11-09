<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cvesat
 * 
 * @property int $id
 * @property string|null $clave
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Cvesat extends Model
{
	protected $table = 'cvesats';

	protected $fillable = [
		'clave',
		'descripcion'
	];
}
