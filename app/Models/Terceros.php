<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Tercero
 *
 * @property int $id
 * @property string $rfc
 * @property string $nombre
 * @property string $tipo
 * @property string $cuenta
 * @property string $telefono
 * @property string $correo
 * @property string $contacto
 * @property string $tax_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Terceros extends Model
{
	protected $table = 'terceros';

	protected $fillable = [
		'rfc',
		'nombre',
		'tipo',
		'cuenta',
		'telefono',
		'correo',
		'contacto',
        'regimen',
        'codigopos',
		'tax_id',
        'team_id'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
