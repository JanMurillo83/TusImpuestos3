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
       /* $cfdis = DB::table('almacencfdis')->where('xml_type','Emitidos')->get();
        foreach($cfdis as $cfdi){
            if(!DB::table('clientes')->where('team_id',$cfdi->team_id)->where('rfc',$cfdi->Receptor_Rfc)->exists())
            {
                $clave = count(DB::table('clientes')->where('team_id',$cfdi->team_id)->get()) + 1;
                DB::table('clientes')->create([
                    'clave'=>$clave,
                    'rfc'=>$cfdi->Receptor_Rfc,
                    'nombre'=>$cfdi->Receptor_Nombre,
                    'team_id'=>$cfdi->team_id,
                    'dias_credito'=>30
                ]);
            }
        }
        DB::statement('UPDATE clientes SET dias_credito = 30 WHERE id > 0');
       */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
