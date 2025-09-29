<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_modelo_partidas', function (Blueprint $table) {
            $table->id();
            $table->integer('factura_modelo_id')->index();
            $table->integer('item');
            $table->string('descripcion');
            $table->decimal('cant',18,8)->default(1);
            $table->decimal('precio',18,8)->default(0);
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('iva',18,8)->default(0);
            $table->decimal('retiva',18,8)->default(0);
            $table->decimal('retisr',18,8)->default(0);
            $table->decimal('ieps',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->string('unidad');
            $table->string('cvesat');
            $table->decimal('costo',18,8)->default(0);
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_modelo_partidas');
    }
};
