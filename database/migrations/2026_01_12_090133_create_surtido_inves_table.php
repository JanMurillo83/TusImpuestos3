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
        Schema::create('surtido_inves', function (Blueprint $table) {
            $table->id();
            $table->integer('factura_id');
            $table->integer('factura_partida_id');
            $table->integer('item_id');
            $table->string('descr')->nullable();
            $table->decimal('cant',18,8)->default(0);
            $table->decimal('precio_u',18,8)->default(0);
            $table->decimal('costo_u',18,8)->default(0);
            $table->decimal('precio_total',18,8)->default(0);
            $table->decimal('costo_total',18,8)->default(0);
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surtido_inves');
    }
};
