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
        Schema::create('saldos_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->string('cuenta');
            $table->string('acumula');
            $table->string('naturaleza');
            $table->decimal('anterior',18,8)->default(0);
            $table->decimal('cargos',18,8)->default(0);
            $table->decimal('abonos',18,8)->default(0);
            $table->decimal('final',18,8)->default(0);
            $table->integer('nivel')->default(0);
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_reportes');
    }
};
