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
        Schema::create('insumos_salidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insumo_id');
            $table->decimal('cantidad', 18, 8)->default(0);
            $table->date('fecha');
            $table->unsignedBigInteger('user_id');
            $table->text('observaciones')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos_salidas');
    }
};
