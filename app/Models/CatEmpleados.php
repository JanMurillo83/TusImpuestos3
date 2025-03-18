<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatEmpleados extends Model
{
    protected $fillable = ['nombre','rfc','curp','imss','sueldo','ret_isr','ret_imss','subsidio','estado','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
