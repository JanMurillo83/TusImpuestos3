<?php

namespace App\Http\Middleware;
use App\Models\CatCuentas;
use App\Models\Activosfijos;
use App\Models\Almacencfdis;
use App\Models\BancoCuentas;
use App\Models\CatBancos;
use App\Models\Clientes;
use App\Models\Compras;
use App\Models\Contribuyentes;
use App\Models\Cotizaciones;
use App\Models\CuentasGastos;
use App\Models\Cuentasxcs;
use App\Models\Cuentasxpagars;
use App\Models\Facturas;
use App\Models\Inventario;
use App\Models\Listareportes;
use App\Models\Movbancos;
use App\Models\Movinventario;
use App\Models\Notasventa;
use App\Models\Ordenes;
use App\Models\Prestamos;
use App\Models\Proveedores;
use App\Models\RubroGastos;
use App\Models\SaldosBancos;
use App\Models\Saldoscuentas;
use App\Models\Solicitudes;
use App\Models\TableSettings;
use App\Models\Team;
use App\Models\Terceros;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        CatCuentas::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Activosfijos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Almacencfdis::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        BancoCuentas::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        CatBancos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        CatCuentas::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Contribuyentes::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        CuentasGastos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Cuentasxcs::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Cuentasxpagars::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Movbancos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Prestamos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        RubroGastos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        SaldosBancos::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Saldoscuentas::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Solicitudes::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Terceros::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Clientes::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Inventario::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Movinventario::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Cotizaciones::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Notasventa::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Facturas::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Ordenes::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Compras::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Proveedores::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        Listareportes::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        TableSettings::addGlobalScope(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),);
        return $next($request);
    }
}
