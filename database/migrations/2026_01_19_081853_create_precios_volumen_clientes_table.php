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
        Schema::create('precios_volumen_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('inventarios')->onDelete('cascade');
            $table->decimal('cantidad_desde', 18, 2);
            $table->decimal('cantidad_hasta', 18, 2)->nullable()->comment('NULL = infinito');
            $table->decimal('precio_unitario', 18, 6);
            $table->boolean('activo')->default(true);
            $table->integer('prioridad')->default(10)->comment('Mayor número = mayor prioridad');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->integer('team_id');
            $table->timestamps();

            $table->index(['cliente_id', 'producto_id', 'activo']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios_volumen_clientes');
    }
};
