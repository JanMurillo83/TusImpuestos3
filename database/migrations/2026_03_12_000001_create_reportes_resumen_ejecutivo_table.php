<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_resumen_ejecutivo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('periodo', 20);
            $table->json('datos');
            $table->longText('reporte');
            $table->timestamps();

            $table->index(['tenant_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_resumen_ejecutivo');
    }
};
