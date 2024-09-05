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
        Schema::create('cuentasxpagars', function (Blueprint $table) {
            $table->id();
            $table->string('rfc');
            $table->string('nombre');
            $table->string('factura');
            $table->dateTime('fecha');
            $table->dateTime('registro');
            $table->decimal('importe', 18, 8);
            $table->decimal('pagado', 18, 8);
            $table->decimal('saldo', 18, 8);
            $table->string('uuid');
            $table->string('tax_id');
            $table->string('espagado')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('cuentasxpagars_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuentasxpagars_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentasxpagars');
    }
};
