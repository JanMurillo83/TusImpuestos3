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
        Schema::create('cuentas_pagar_tables', function (Blueprint $table) {
            $table->id();
            $table->string('cliente')->nullable();
            $table->string('documento')->nullable();
            $table->string('uuid')->nullable();
            $table->string('concepto')->nullable();
            $table->date('fecha')->nullable();
            $table->date('vencimiento')->nullable();
            $table->decimal('importe',18,8)->nullable();
            $table->decimal('saldo',18,8)->nullable();
            $table->string('tipo')->nullable();
            $table->integer('periodo');
            $table->integer('ejercicio');
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_pagar_tables');
    }
};
