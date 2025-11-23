<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatCuentas extends Model
{
    use HasFactory;
    protected $fillable =['codigo','nombre','acumula','tipo','naturaleza','csat','team_id','rfc_asociado'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

}
