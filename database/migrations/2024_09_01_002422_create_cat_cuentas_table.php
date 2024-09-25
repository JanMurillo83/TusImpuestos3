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
        Schema::create('cat_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('nombre')->nullable();
            $table->string('acumula')->nullable();
            $table->string('tipo')->nullable();
            $table->string('naturaleza')->nullable();
            $table->string('csat')->nullable();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
        Schema::create('cat_cuentas_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cat_cuentas_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_cuentas');
        Schema::dropIfExists('cat_cuenta_team');
    }
};
