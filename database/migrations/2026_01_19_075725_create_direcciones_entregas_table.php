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
        Schema::create('direcciones_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nombre_sucursal', 255);
            $table->string('calle', 255)->nullable();
            $table->string('no_exterior', 50)->nullable();
            $table->string('no_interior', 50)->nullable();
            $table->string('colonia', 255)->nullable();
            $table->string('municipio', 255)->nullable();
            $table->string('estado', 255)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('telefono', 255)->nullable();
            $table->string('contacto', 255)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones_entregas');
    }
};
