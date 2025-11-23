<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*$provees = DB::table('clientes')->where('id','>',0)->get();
        foreach($provees as $provee)
        {
            try {
                if (DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->exists()) {
                    $ctaprove = \App\Models\CatCuentas::where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->first();
                    DB::table('proveedores')->where('id',$provee->id)->update(['cuenta_contable'=>$ctaprove->codigo]);
                    DB::table('cat_cuentas')->where('id',$ctaprove->id)->update(['rfc_asociado'=>$provee->rfc]);
                } else {
                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '10501000')->max('codigo')) + 1;
                    DB::table('cat_cuentas')->create([
                        'nombre' => $provee->nombre,
                        'team_id' => $provee->team_id,
                        'codigo' => $nuecta,
                        'acumula' => '10501000',
                        'tipo' => 'D',
                        'naturaleza' => 'A',
                        'rfc_asociado' => $provee->rfc
                    ]);
                    DB::table('clientes')->where('id',$provee->id)->update(['cuenta_contable'=>$nuecta]);

                }
            }catch(Exception $e)
            {
                error_log($e->getMessage());
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
