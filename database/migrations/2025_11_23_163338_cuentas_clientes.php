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
        $provees = \App\Models\Clientes::where('id','>',0)->get();
        foreach($provees as $provee)
        {
            try {
                if (\App\Models\CatCuentas::where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->exists()) {
                    $ctaprove = \App\Models\CatCuentas::where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->first();
                    $provee->cuenta_contable = $ctaprove->codigo;
                    $provee->save();
                    $ctaprove->rfc_asociado = $provee->rfc;
                    $ctaprove->save();
                } else {
                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '10501000')->max('codigo')) + 1;
                    \App\Models\CatCuentas::firstOrCreate([
                        'nombre' => $provee->nombre,
                        'team_id' => $provee->team_id,
                        'codigo' => $nuecta,
                        'acumula' => '10501000',
                        'tipo' => 'D',
                        'naturaleza' => 'A',
                        'rfc_asociado' => $provee->rfc
                    ]);
                    $provee->cuenta_contable = $nuecta;
                    $provee->save();
                }
            }catch(Exception $e)
            {
                error_log($e->getMessage());
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
