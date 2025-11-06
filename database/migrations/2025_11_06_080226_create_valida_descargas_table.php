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
        Schema::create('valida_descargas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->date('inicio');
            $table->date('fin');
            $table->integer('recibidos');
            $table->integer('emitidos');
            $table->string('estado');
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valida_descargas');
    }
};
