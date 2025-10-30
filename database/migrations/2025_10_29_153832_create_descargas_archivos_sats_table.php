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
        Schema::create('descargas_archivos_sats', function (Blueprint $table) {
            $table->id();
            $table->string('id_sat');
            $table->integer('team_id');
            $table->date('fecha');
            $table->string('archivo');
            $table->string('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descargas_archivos_sats');
    }
};
