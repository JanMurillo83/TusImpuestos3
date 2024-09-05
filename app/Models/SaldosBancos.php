<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SaldosBanco
 *
 * @property int $id
 * @property int|null $banco
 * @property int|null $periodo
 * @property int|null $saldo
 *
 * @package App\Models
 */
class SaldosBancos extends Model
{
	protected $table = 'saldos_banco';
	public $timestamps = false;

	protected $casts = [
		'banco' => 'int',
		'periodo' => 'int',
		'saldo' => 'int'
	];

	protected $fillable = [
		'banco',
		'periodo',
		'saldo',
        'team_id'
	];
}
