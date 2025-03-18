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
        Schema::create('pago_nominas_detalles', function (Blueprint $table) {
            $table->id();
            $table->integer('pago_nominas_id');
            $table->integer('empleado');
            $table->text('recibo')->nullable();
            $table->decimal('sueldo',18,8)->default(0);
            $table->decimal('ret_isr',18,8)->default(0);
            $table->decimal('ret_imss',18,8)->default(0);
            $table->decimal('subsidio',18,8)->default(0);
            $table->decimal('otras_per',18,8)->default(0);
            $table->decimal('otras_ded',18,8)->default(0);
            $table->decimal('importe',18,8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_nominas_detalles');
    }
};
