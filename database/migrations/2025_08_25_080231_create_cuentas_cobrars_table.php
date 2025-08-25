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
        Schema::create('cuentas_cobrars', function (Blueprint $table) {
            $table->id();
            $table->integer('cliente');
            $table->integer('concepto');
            $table->string('descripcion')->nullable();
            $table->string('documento')->nullable();
            $table->date('fecha');
            $table->date('vencimiento');
            $table->decimal('importe',18,8)->default(0);
            $table->decimal('saldo',18,8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_cobrars');
    }
};
