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
        Schema::create('xmlfiles', function (Blueprint $table) {
            $table->id();
            $table->string('taxid', 45)->nullable();
            $table->string('uuid', 500)->nullable();
            $table->longText('content')->nullable();
            $table->integer('periodo')->nullable();
            $table->integer('ejercicio')->nullable();
            $table->string('tipo', 45)->nullable();
            $table->string('solicitud', 500)->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('xmlfiles_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xmlfiles_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xmlfiles');
    }
};
