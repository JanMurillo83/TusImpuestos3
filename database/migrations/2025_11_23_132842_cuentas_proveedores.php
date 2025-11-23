<?php

use App\Models\CatCuentas;
use App\Models\Terceros;
use Filament\Facades\Filament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $provees = \App\Models\Proveedores::where('id','>',0)->get();
        foreach($provees as $provee)
        {

            if (CatCuentas::where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id',$provee->team_id)->exists())
            {
                $ctaprove = CatCuentas::where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id',$provee->team_id)->first();
                $provee->cuenta_contable = $ctaprove->codigo;
                $provee->save();
                $ctaprove->rfc_asociado = $provee->rfc;
                $ctaprove->save();
            }
            else
            {
                $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '20101000')->max('codigo')) + 1;
                CatCuentas::firstOrCreate([
                    'nombre' => $provee->nombre,
                    'team_id' => $provee->team_id,
                    'codigo' => $nuecta,
                    'acumula' => '20101000',
                    'tipo' => 'D',
                    'naturaleza' => 'A',
                    'rfc_asociado' => $provee->rfc
                ]);
                $provee->cuenta_contable = $nuecta;
                $provee->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
