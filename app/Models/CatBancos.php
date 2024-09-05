<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CatBanco
 *
 * @property int $id
 * @property string $clave
 * @property string $banco
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class CatBancos extends Model
{
	protected $table = 'cat_bancos';

	protected $fillable = [
		'clave',
		'banco',
        'team_id'
	];
}
