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
        Schema::create('equivalencias_inventario_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('clave_articulo');
            $table->string('clave_cliente');
            $table->string('descripcion_articulo', 1000);
            $table->string('descripcion_cliente', 1000);
            $table->decimal('precio_cliente', 18, 8)->default(0);
            $table->integer('team_id')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'cliente_id', 'clave_articulo', 'clave_cliente'], 'equiv_inv_clie_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencias_inventario_clientes');
    }
};
