<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Contribuyente
 *
 * @property int $id
 * @property string $rfc
 * @property string $nombre
 * @property string $responsable
 * @property string $contacto
 * @property string $archivokey
 * @property string $archivocer
 * @property string $password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Contribuyentes extends Model
{
	protected $table = 'contribuyentes';

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'rfc',
		'nombre',
		'responsable',
		'contacto',
		'archivokey',
		'archivocer',
		'password',
        'team_id'
	];
}
