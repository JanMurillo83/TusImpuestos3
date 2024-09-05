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
        Schema::create('cuentas_gastos', function (Blueprint $table) {
            $table->id();
            $table->integer('rubro');
            $table->string('cuenta');
            $table->string('concepto');
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('cuentas_gastos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuentas_gastos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_gastos');
    }
};
