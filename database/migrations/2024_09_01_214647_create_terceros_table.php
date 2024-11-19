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
        Schema::create('terceros', function (Blueprint $table) {
            $table->id();
            $table->string('rfc');
            $table->string('nombre');
            $table->string('tipo');
            $table->string('cuenta');
            $table->string('telefono');
            $table->string('correo');
            $table->string('contacto');
            $table->string('regimen')->nullable();
            $table->string('codigopos')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('team_terceros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terceros_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terceros');
    }
};
