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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->integer('folio');
            $table->date('fecha');
            $table->integer('prov');
            $table->string('nombre');
            $table->integer('esquema');
            $table->decimal('subtotal',18,8);
            $table->decimal('iva',18,8);
            $table->decimal('retiva',18,8);
            $table->decimal('retisr',18,8);
            $table->decimal('ieps',18,8);
            $table->decimal('total',18,8);
            $table->text('observa')->nullable();
            $table->string('estado')->nullable();
            $table->integer('orden')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
