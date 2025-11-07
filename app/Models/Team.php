<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;
    protected $fillable =['name','taxid','archivokey','archivocer','fielpass','periodo',
    'ejercicio','regimen','codigopos','csdkey','csdcer','csdpass','claveciec','vigencia_fiel','estado_fiel','descarga_cfdi'];
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
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Productos::class);
    }
    public function movinventarios(): BelongsToMany
    {
        return $this->belongsToMany(Movinventarios::class);
    }
    public function compras(): BelongsToMany
    {
        return $this->belongsToMany(Compras::class);
    }
    public function facturas(): BelongsToMany
    {
        return $this->belongsToMany(Compras::class);
    }
    public function terceros(): BelongsToMany
    {
        return $this->belongsToMany(Terceros::class);
    }
    public function activosfijos(): BelongsToMany
    {
        return $this->belongsToMany(Activosfijos::class);
    }

    public function historicoTcs(): BelongsToMany
    {
        return $this->belongsToMany(HistoricoTc::class);
    }

    public function cuentasCobrar(): BelongsToMany
    {
        return $this->belongsToMany(CuentasCobrar::class);
    }

    public function cuentasPagar(): BelongsToMany
    {
        return $this->belongsToMany(CuentasPagar::class);
    }

}
