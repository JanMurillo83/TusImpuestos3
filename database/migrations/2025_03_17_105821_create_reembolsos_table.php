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
        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('periodo');
            $table->integer('ejercicio');
            $table->integer('movbanco')->default(0);
            $table->decimal('importe',18,8)->default(0);
            $table->decimal('importe_comp',18,8)->default(0);
            $table->string('estado');
            $table->integer('idtercero')->default(0);
            $table->string('nombre')->nullable();
            $table->string('formapago')->nullable();
            $table->string('descrfpago')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('notas')->nullable();
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reembolsos');
    }
};
