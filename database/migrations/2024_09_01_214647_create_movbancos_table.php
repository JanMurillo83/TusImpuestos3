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
        Schema::create('movbancos', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('tipo')->nullable();
            $table->string('tercero')->nullable();
            $table->string('cuenta')->nullable();
            $table->string('factura')->nullable();
            $table->string('uuid')->nullable();
            $table->decimal('importe', 18, 8)->nullable();
            $table->string('concepto')->nullable();
            $table->string('contabilizada', 45)->default('NO');
            $table->string('movbancoscol', 45)->nullable();
            $table->integer('ejercicio')->nullable();
            $table->integer('periodo')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('movbancos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movbancos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movbancos');
    }
};
