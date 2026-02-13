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
        Schema::create('auxiliares_iva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auxiliares_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();

            // Datos del IVA
            $table->decimal('base_gravable', 18, 2)->default(0)->comment('Base para cálculo de IVA');
            $table->decimal('tasa_iva', 5, 2)->default(0)->comment('Tasa de IVA (0, 8, 16)');
            $table->decimal('importe_iva', 18, 2)->default(0)->comment('Importe del IVA');
            $table->decimal('retencion_iva', 18, 2)->default(0)->comment('Retención de IVA');
            $table->decimal('retencion_isr', 18, 2)->default(0)->comment('Retención de ISR');
            $table->decimal('ieps', 18, 2)->default(0)->comment('IEPS');

            // Clasificación fiscal
            $table->enum('tipo_operacion', ['acreditable', 'no_acreditable', 'importacion', 'pendiente'])->default('acreditable');
            $table->string('tipo_comprobante')->nullable()->comment('Ingreso, Egreso, Pago, Traslado, Nómina');
            $table->string('metodo_pago')->nullable()->comment('PUE, PPD');

            // Referencias
            $table->string('uuid')->nullable()->comment('UUID del CFDI relacionado');
            $table->string('folio_fiscal')->nullable();

            $table->timestamps();

            // Índices para mejorar consultas
            $table->index(['auxiliares_id', 'team_id']);
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliares_iva');
    }
};
