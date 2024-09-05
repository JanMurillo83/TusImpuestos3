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
        Schema::create('cat_polizas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->integer('folio');
            $table->dateTime('fecha');
            $table->string('concepto');
            $table->decimal('cargos', 18, 8);
            $table->decimal('abonos', 18, 8);
            $table->integer('periodo');
            $table->integer('ejercicio');
            $table->string('referencia')->nullable();
            $table->string('uuid')->nullable();
            $table->string('tiposat')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('cat_polizas_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cat_polizas_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_polizas_nima831222hz9');
    }
};
