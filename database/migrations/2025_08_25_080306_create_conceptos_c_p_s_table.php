<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conceptos_c_p_s', function (Blueprint $table) {
            $table->id();
            $table->string('clave');
            $table->string('descripcion')->nullable();
            $table->string('tipo')->nullable();
            $table->string('signo');
            $table->string('pagosat')->nullable();
            $table->timestamps();
        });
        DB::table('conceptos_c_p_s')->insert([
            ['clave'=>'1','descripcion'=>'factura','tipo'=>'C','signo'=>'1','pagosat'=>''],
            ['clave'=>'2','descripcion'=>'Notas de cargo','tipo'=>'C','signo'=>'1','pagosat'=>''],
            ['clave'=>'3','descripcion'=>'Anticipo','tipo'=>'A','signo'=>'-1','pagosat'=>''],
            ['clave'=>'4','descripcion'=>'Efectivo','tipo'=>'A','signo'=>'-1','pagosat'=>'1'],
            ['clave'=>'5','descripcion'=>'Cheque','tipo'=>'A','signo'=>'-1','pagosat'=>'2'],
            ['clave'=>'6','descripcion'=>'Nota devoluci?n','tipo'=>'A','signo'=>'-1','pagosat'=>'4'],
            ['clave'=>'7','descripcion'=>'Canc. Anticipo.','tipo'=>'C','signo'=>'1','pagosat'=>''],
            ['clave'=>'8','descripcion'=>'Compensacion','tipo'=>'A','signo'=>'-1','pagosat'=>'17'],
            ['clave'=>'9','descripcion'=>'Transf. de Fondos','tipo'=>'A','signo'=>'-1','pagosat'=>'3'],
            ['clave'=>'10','descripcion'=>'Factura a plazos','tipo'=>'C','signo'=>'1','pagosat'=>''],
            ['clave'=>'11','descripcion'=>'Ganancia camb.','tipo'=>'C','signo'=>'1','pagosat'=>''],
            ['clave'=>'12','descripcion'=>'Perdida camb.','tipo'=>'A','signo'=>'-1','pagosat'=>''],
            ['clave'=>'13','descripcion'=>'Cta. incobrable','tipo'=>'A','signo'=>'-1','pagosat'=>''],
            ['clave'=>'14','descripcion'=>'Nota cred. x apl.','tipo'=>'A','signo'=>'-1','pagosat'=>''],
            ['clave'=>'15','descripcion'=>'Nota de cr?dito','tipo'=>'A','signo'=>'-1','pagosat'=>''],
            ['clave'=>'16','descripcion'=>'Pago con CoDi','tipo'=>'A','signo'=>'-1','pagosat'=>'3']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos_c_p_s');
    }
};
