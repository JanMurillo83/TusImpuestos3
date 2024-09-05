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
        Schema::create('rubro_gastos', function (Blueprint $table) {
            $table->id();
            $table->integer('rubro');
            $table->string('nombre');
            $table->string('mayor');
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('rubro_gastos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubro_gastos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubro_gastos');
    }
};
