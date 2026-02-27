<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo');
            $table->date('fecha');
            $table->text('resultado')->nullable();
            $table->text('proxima_accion')->nullable();
            $table->date('proxima_fecha')->nullable();
            $table->timestamps();

            $table->index(['cotizacion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_actividades');
    }
};
