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
        Schema::create('pago_nominas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('nonom')->default(0)->nullable();
            $table->string('tipo')->nullable();
            $table->date('fecha_pa');
            $table->decimal('sueldo',18,8)->default(0);
            $table->decimal('ret_isr',18,8)->default(0);
            $table->decimal('ret_imss',18,8)->default(0);
            $table->decimal('subsidio',18,8)->default(0);
            $table->decimal('otras_per',18,8)->default(0);
            $table->decimal('otras_ded',18,8)->default(0);
            $table->decimal('importe',18,8)->default(0);
            $table->integer('movban');
            $table->string('estado')->nullable();
            $table->integer('team_id');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_nominas');
    }
};
