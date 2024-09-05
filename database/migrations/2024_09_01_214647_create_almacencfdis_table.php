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
        Schema::create('almacencfdis', function (Blueprint $table) {
            $table->id();
            $table->string('Serie')->nullable();
            $table->string('Folio')->nullable();
            $table->string('Version')->nullable();
            $table->string('Fecha')->nullable();
            $table->string('Moneda')->nullable();
            $table->string('TipoDeComprobante')->nullable();
            $table->string('MetodoPago')->nullable();
            $table->string('Emisor_Rfc')->nullable();
            $table->string('Emisor_Nombre')->nullable();
            $table->string('Emisor_RegimenFiscal')->nullable();
            $table->string('Receptor_Rfc')->nullable();
            $table->string('Receptor_Nombre')->nullable();
            $table->string('Receptor_RegimenFiscal')->nullable();
            $table->string('UUID')->nullable();
            $table->decimal('Total', 18, 8)->nullable();
            $table->decimal('SubTotal', 18, 8)->nullable();
            $table->decimal('TipoCambio', 18, 8)->nullable();
            $table->decimal('TotalImpuestosTrasladados', 18, 8)->nullable();
            $table->decimal('TotalImpuestosRetenidos', 18, 8)->nullable();
            $table->text('content')->nullable();
            $table->string('user_tax');
            $table->string('used');
            $table->string('xml_type');
            $table->string('metodo')->nullable();
            $table->integer('ejercicio')->nullable();
            $table->integer('periodo')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('almacencfdis_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacencfdis_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacencfdis');
    }
};
