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
        /*$provees = DB::table('proveedores')->where('id','>',0)->get();
        foreach($provees as $provee)
        {

            if (DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id',$provee->team_id)->exists())
            {
                $ctaprove = DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id',$provee->team_id)->first();
                DB::table('proveedores')->where('id',$provee->id)->update(['cuenta_contable'=>$ctaprove->codigo]);
                DB::table('cat_cuentas')->where('id',$ctaprove->id)->update(['rfc_asociado'=>$provee->rfc]);
            }
            else
            {
                $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '20101000')->max('codigo')) + 1;
                DB::table('cat_cuentas')->create([
                    'nombre' => $provee->nombre,
                    'team_id' => $provee->team_id,
                    'codigo' => $nuecta,
                    'acumula' => '20101000',
                    'tipo' => 'D',
                    'naturaleza' => 'A',
                    'rfc_asociado' => $provee->rfc
                ]);
                DB::table('proveedores')->where('id',$provee->id)->update(['cuenta_contable'=>$nuecta]);
            }
        }*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
