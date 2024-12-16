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
        Schema::create('ordenes_partidas', function (Blueprint $table) {
            $table->id();
            $table->integer('ordenes_id');
            $table->integer('item');
            $table->string('descripcion');
            $table->decimal('cant',18,8)->default(0);
            $table->decimal('costo',18,8)->default(0);
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('iva',18,8)->default(0);
            $table->decimal('retiva',18,8)->default(0);
            $table->decimal('retisr',18,8)->default(0);
            $table->decimal('ieps',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->string('unidad')->nullable();
            $table->string('cvesat')->nullable();
            $table->integer('prov');
            $table->text('observa')->nullable();
            $table->integer('idcompra')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_partidas');
    }
};
