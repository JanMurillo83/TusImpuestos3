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
        Schema::create('temp_cfdis', function (Blueprint $table) {
            $table->id();
            $table->string('UUID')->nullable();
            $table->string('RfcEmisor')->nullable();
            $table->string('NombreEmisor')->nullable();
            $table->string('RfcReceptor')->nullable();
            $table->string('NombreReceptor')->nullable();
            $table->string('RfcPac')->nullable();
            $table->string('FechaEmision')->nullable();
            $table->string('FechaCertificacionSat')->nullable();
            $table->decimal('Monto',18,8)->nullable();
            $table->string('EfectoComprobante')->nullable();
            $table->string('Estatus')->nullable();
            $table->string('FechaCancelacion')->nullable();
            $table->string('Tipo')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_cfdis');
    }
};
