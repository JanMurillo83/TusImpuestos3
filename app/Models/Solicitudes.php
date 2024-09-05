<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Solicitude
 *
 * @property int $id
 * @property string $request_id
 * @property string $status
 * @property string $message
 * @property string $xml_type
 * @property string $ini_date
 * @property string $ini_hour
 * @property string $end_date
 * @property string $end_hour
 * @property string $user_tax
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Solicitudes extends Model
{
	protected $table = 'solicitudes';

	protected $fillable = [
		'request_id',
		'status',
		'message',
		'xml_type',
		'ini_date',
		'ini_hour',
		'end_date',
		'end_hour',
		'user_tax',
        'team_id'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
