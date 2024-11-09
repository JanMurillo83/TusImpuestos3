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
        Schema::create('unid_prods', function (Blueprint $table) {
            $table->id();
            $table->string('unidad')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('unidad_sat')->nullable();
            $table->string('atributo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unid_prods');
    }
};
