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
        Schema::create('saldos_bancos', function (Blueprint $table) {
            $table->id();
            $table->integer('banco')->nullable();
            $table->integer('periodo')->nullable();
            $table->integer('saldo')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('saldos_bancos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldos_bancos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_banco');
    }
};
