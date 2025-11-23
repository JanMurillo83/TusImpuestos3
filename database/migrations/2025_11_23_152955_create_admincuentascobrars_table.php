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
        Schema::create('admincuentascobrars', function (Blueprint $table) {
            $table->id();
            $table->integer('clave');
            $table->integer('referencia');
            $table->string('uuid');
            $table->date('fecha');
            $table->date('vencimiento');
            $table->string('moneda');
            $table->decimal('tcambio',18,8)->default(1);
            $table->decimal('importe',18,8)->default(0);
            $table->decimal('importeusd',18,8)->default(0);
            $table->decimal('saldo',18,8)->default(0);
            $table->decimal('saldousd',18,8)->default(0);
            $table->integer('periodo');
            $table->integer('ejercicio');
            $table->integer('periodo_ven');
            $table->integer('ejercicio_ven');
            $table->integer('poliza');
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admincuentascobrars');
    }
};
