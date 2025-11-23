<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*try {
            $cfdis = \App\Models\Almacencfdis::where('xml_type', 'Recibidos')->where('TipoDeComprobante', 'I')->get();
            foreach ($cfdis as $cfdi) {
                if (!DB::table('proveedores')->where('team_id', $cfdi->team_id)->where('rfc', $cfdi->Emisor_Rfc)->exists()) {
                    $clave = count(DB::table('proveedores')->where('team_id', $cfdi->team_id)->get()) + 1;
                    \App\Models\Proveedores::create([
                        'clave' => $clave,
                        'rfc' => $cfdi->Emisor_Rfc,
                        'nombre' => $cfdi->Emisor_Nombre,
                        'team_id' => $cfdi->team_id,
                        'dias_credito' => 30
                    ]);
                }
            }
            \Illuminate\Support\Facades\DB::statement('UPDATE proveedores SET dias_credito = 30 WHERE id > 0');
        }catch(Exception $e){
            error_log($e->getMessage());
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
