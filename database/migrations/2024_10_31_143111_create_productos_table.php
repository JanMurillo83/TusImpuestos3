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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->nullable();
            $table->string('descripcion',1000)->nullable();
            $table->string('unidad')->nullable();
            $table->string('clavesat')->nullable();
            $table->decimal('existencia',18,8)->default(0);
            $table->decimal('precio',18,8)->default(0);
            $table->decimal('costo_u',18,8)->default(0);
            $table->decimal('costo_p',18,8)->default(0);
            $table->string('codigo')->nullable();
            $table->integer('team_id');
            $table->timestamps();
        });
        Schema::create('productos_team', function (Blueprint $table) {
            $table->foreignId('productos_id')->constrained();
            $table->foreignId('team_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_team');
        Schema::dropIfExists('productos');
    }
};
