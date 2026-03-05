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
        Schema::create('nomina_concepto_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->string('tipo');
            $table->string('codigo_sat')->nullable();
            $table->string('clave')->nullable();
            $table->string('descripcion')->nullable();
            $table->foreignId('cat_cuentas_id')->constrained('cat_cuentas');
            $table->string('naturaleza', 1)->default('D');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'tipo', 'codigo_sat']);
            $table->index(['team_id', 'tipo', 'clave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_concepto_cuentas');
    }
};
