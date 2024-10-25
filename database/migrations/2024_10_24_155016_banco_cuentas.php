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
        Schema::create('banco_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->nullable();
            $table->string('banco')->nullable();
            $table->string('codigo')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('cuenta')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('banco_cuentas_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_cuentas_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
