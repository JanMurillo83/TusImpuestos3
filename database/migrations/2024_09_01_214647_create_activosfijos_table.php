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
        Schema::create('activosfijos', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('serie')->nullable();
            $table->string('proveedor')->nullable();
            $table->decimal('importe', 18, 8);
            $table->decimal('depre', 18, 8);
            $table->decimal('acumulado', 18, 8);
            $table->string('cuentadep')->nullable();
            $table->string('cuentaact')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('activosfijos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activosfijos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activosfijos');
    }
};
