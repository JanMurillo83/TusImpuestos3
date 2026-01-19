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
        Schema::create('precios_volumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('inventarios')->onDelete('cascade');
            $table->tinyInteger('lista_precio')->comment('1,2,3,4,5');
            $table->decimal('cantidad_desde', 18, 2);
            $table->decimal('cantidad_hasta', 18, 2)->nullable()->comment('NULL = infinito');
            $table->decimal('precio_unitario', 18, 6);
            $table->boolean('activo')->default(true);
            $table->integer('team_id');
            $table->timestamps();

            $table->index(['producto_id', 'lista_precio', 'activo']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios_volumen');
    }
};
