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
        Schema::table('ordenes', function (Blueprint $table) {
            $table->string('entrega_lugar')->nullable();
            $table->string('entrega_direccion')->nullable();
            $table->string('entrega_horario')->nullable();
            $table->string('entrega_contacto')->nullable();
            $table->string('entrega_telefono')->nullable();
            $table->string('condiciones_pago')->nullable();
            $table->string('condiciones_entrega')->nullable();
            $table->string('oc_referencia_interna')->nullable();
            $table->string('nombre_elaboro')->nullable();
            $table->string('nombre_autorizo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn([
                'entrega_lugar',
                'entrega_direccion',
                'entrega_horario',
                'entrega_contacto',
                'entrega_telefono',
                'condiciones_pago',
                'condiciones_entrega',
                'oc_referencia_interna',
                'nombre_elaboro',
                'nombre_autorizo',
            ]);
        });
    }
};
