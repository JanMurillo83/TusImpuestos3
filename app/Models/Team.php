<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;
    protected $fillable =['name','taxid','archivokey','archivocer','fielpass','periodo','ejercicio'];
    public function catPolizas(): BelongsToMany
    {
        return $this->belongsToMany(CatPolizas::class);
    }
    public function catCuentas(): BelongsToMany
    {
        return $this->belongsToMany(CatCuentas::class);
    }
    public function bancoCuentas(): BelongsToMany
    {
        return $this->belongsToMany(BancoCuentas::class);
    }
    public function movbancos(): BelongsToMany
    {
        return $this->belongsToMany(Movbancos::class);
    }

}
