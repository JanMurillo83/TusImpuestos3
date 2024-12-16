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
        Schema::create('movinventarios', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->string('tipo');
            $table->string('fecha');
            $table->decimal('cant');
            $table->decimal('costo')->default(0);
            $table->decimal('precio')->default(0);
            $table->string('concepto');
            $table->string('tipoter');
            $table->integer('tercero')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movinventarios');
    }
};
