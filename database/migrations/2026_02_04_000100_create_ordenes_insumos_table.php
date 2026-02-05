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
        Schema::create('ordenes_insumos', function (Blueprint $table) {
            $table->id();
            $table->integer('folio');
            $table->date('fecha');
            $table->integer('prov');
            $table->string('nombre');
            $table->integer('esquema');
            $table->decimal('subtotal', 18, 8);
            $table->decimal('iva', 18, 8);
            $table->decimal('retiva', 18, 8);
            $table->decimal('retisr', 18, 8);
            $table->decimal('ieps', 18, 8);
            $table->decimal('total', 18, 8);
            $table->string('moneda')->default('MXN');
            $table->decimal('tcambio')->default(1);
            $table->text('observa')->nullable();
            $table->string('estado')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();
            $table->integer('team_id')->nullable();
            $table->string('solicita')->nullable();
            $table->integer('proyecto')->nullable();
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_insumos');
    }
};
