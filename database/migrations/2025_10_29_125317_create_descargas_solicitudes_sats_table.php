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
        Schema::create('descargas_solicitudes_sats', function (Blueprint $table) {
            $table->id();
            $table->string('id_sat');
            $table->string('estatus');
            $table->string('estado');
            $table->integer('team_id');
            $table->dateTime('fecha_inicial');
            $table->dateTime('fecha_final');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descargas_solicitudes_sats');
    }
};
