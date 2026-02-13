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
        Schema::create('auxiliares_diot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auxiliares_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();

            // Datos del proveedor
            $table->string('rfc_proveedor', 13)->nullable();
            $table->string('nombre_proveedor')->nullable();
            $table->string('pais_residencia', 3)->default('MEX')->comment('Código país ISO 3');

            // Tipo de operación DIOT (según anexo 8 de la DIOT)
            $table->string('tipo_operacion', 2)->nullable()->comment('03=Arrendamiento, 04=Otros, 05=Honorarios, 06=Fletes, etc');
            $table->string('tipo_tercero', 2)->nullable()->comment('04=Proveedor, 05=Arrendador, 15=Extranjero, etc');

            // Montos para DIOT
            $table->decimal('importe_pagado_16', 18, 2)->default(0)->comment('Operaciones con IVA 16%');
            $table->decimal('iva_pagado_16', 18, 2)->default(0)->comment('IVA pagado 16%');
            $table->decimal('importe_pagado_8', 18, 2)->default(0)->comment('Operaciones con IVA 8%');
            $table->decimal('iva_pagado_8', 18, 2)->default(0)->comment('IVA pagado 8%');
            $table->decimal('importe_pagado_0', 18, 2)->default(0)->comment('Operaciones tasa 0%');
            $table->decimal('importe_exento', 18, 2)->default(0)->comment('Operaciones exentas');
            $table->decimal('iva_retenido', 18, 2)->default(0)->comment('IVA retenido');
            $table->decimal('isr_retenido', 18, 2)->default(0)->comment('ISR retenido');

            // Datos de la operación
            $table->string('numero_operacion')->nullable()->comment('Número de pedimento, escritura pública, etc');
            $table->date('fecha_operacion')->nullable();

            // Control
            $table->boolean('incluir_en_diot')->default(true);

            $table->timestamps();

            // Índices
            $table->index(['auxiliares_id', 'team_id']);
            $table->index('rfc_proveedor');
            $table->index(['fecha_operacion', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliares_diot');
    }
};
