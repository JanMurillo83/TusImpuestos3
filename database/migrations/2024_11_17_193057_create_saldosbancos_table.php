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
        Schema::create('saldosbancos', function (Blueprint $table) {
            $table->id();
            $table->integer('cuenta')->default(0);
            $table->decimal('inicial',18,8)->default(0);
            $table->decimal('ingresos',18,8)->default(0);
            $table->decimal('egresos',18,8)->default(0);
            $table->decimal('actual',18,8)->default(0);
            $table->integer('ejercicio')->default(0);
            $table->integer('periodo')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldosbancos');
    }
};
