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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('tercero');
            $table->dateTime('fecha');
            $table->string('tipo');
            $table->decimal('importe', 18, 8);
            $table->decimal('reembolso', 18, 8)->nullable();
            $table->string('cuenta')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('prestamos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
