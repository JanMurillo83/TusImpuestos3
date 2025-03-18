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
        Schema::create('reembolsos_detalles', function (Blueprint $table) {
            $table->id();
            $table->integer('reembolsos_id');
            $table->integer('comprobante');
            $table->string('referencia')->nullable();
            $table->date('fecha');
            $table->string('moneda')->nullable();
            $table->decimal('importe',18,8)->nullable();
            $table->string('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reembolsos_detalles');
    }
};
