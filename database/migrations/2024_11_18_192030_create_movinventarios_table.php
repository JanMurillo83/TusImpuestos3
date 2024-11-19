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
        Schema::create('movinventarios', function (Blueprint $table) {
            $table->id();
            $table->integer('folio')->default(0);
            $table->date('fecha');
            $table->string('tipo')->nullable();
            $table->integer('producto')->default(0);
            $table->string('descripcion')->nullable();
            $table->string('concepto')->nullable();
            $table->string('tipoter')->nullable();
            $table->integer('idter')->default(0);
            $table->string('nomter')->nullable();
            $table->decimal('cant',18,8)->default(0);
            $table->decimal('costou',18,8)->default(0);
            $table->decimal('costot',18,8)->default(0);
            $table->decimal('preciou',18,8)->default(0);
            $table->decimal('preciot',18,8)->default(0);
            $table->integer('periodo')->default(0);
            $table->integer('ejercicio')->default(0);
            $table->integer('team_id')->default(0);
            $table->timestamps();
        });
        Schema::create('movinventarios_team', function (Blueprint $table) {
            $table->foreignId('movinventarios_id')->constrained();
            $table->foreignId('team_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movinventarios');
    }
};
