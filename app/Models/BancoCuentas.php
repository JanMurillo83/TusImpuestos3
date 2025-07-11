<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Class BancoCuenta
 *
 * @property int $id
 * @property string $clave
 * @property string $banco
 * @property string $codigo
 * @property string $tax_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $cuenta
 *
 * @package App\Models
 */
class BancoCuentas extends Model
{
	protected $table = 'banco_cuentas';

	protected $fillable = [
		'clave',
		'banco',
		'codigo',
		'tax_id',
		'cuenta',
        'moneda',
        'team_id',
        'inicial'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
