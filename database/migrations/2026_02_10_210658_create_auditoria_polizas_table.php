<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_polizas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poliza_id');
            $table->string('accion'); // 'crear' o 'modificar'
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('user_email')->nullable();
            $table->json('datos_anteriores')->nullable(); // Para modificaciones
            $table->json('datos_nuevos')->nullable();
            $table->string('origen')->nullable(); // Filament, API, Comando, etc.
            $table->timestamp('fecha_hora');
            $table->timestamps();

            $table->foreign('poliza_id')->references('id')->on('cat_polizas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['poliza_id', 'fecha_hora']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_polizas');
    }
};
