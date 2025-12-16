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
        Schema::create('saldos_anuales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('acumula')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('tipo')->nullable();
            $table->string('naturaleza')->nullable();
            $table->decimal('inicial',18,8)->nullable();
            $table->decimal('c1',18,8)->nullable();
            $table->decimal('a1',18,8)->nullable();
            $table->decimal('f1',18,8)->nullable();
            $table->decimal('c2',18,8)->nullable();
            $table->decimal('a2',18,8)->nullable();
            $table->decimal('f2',18,8)->nullable();
            $table->decimal('c3',18,8)->nullable();
            $table->decimal('a3',18,8)->nullable();
            $table->decimal('f3',18,8)->nullable();
            $table->decimal('c4',18,8)->nullable();
            $table->decimal('a4',18,8)->nullable();
            $table->decimal('f4',18,8)->nullable();
            $table->decimal('c5',18,8)->nullable();
            $table->decimal('a5',18,8)->nullable();
            $table->decimal('f5',18,8)->nullable();
            $table->decimal('c6',18,8)->nullable();
            $table->decimal('a6',18,8)->nullable();
            $table->decimal('f6',18,8)->nullable();
            $table->decimal('c7',18,8)->nullable();
            $table->decimal('a7',18,8)->nullable();
            $table->decimal('f7',18,8)->nullable();
            $table->decimal('c8',18,8)->nullable();
            $table->decimal('a8',18,8)->nullable();
            $table->decimal('f8',18,8)->nullable();
            $table->decimal('c9',18,8)->nullable();
            $table->decimal('a9',18,8)->nullable();
            $table->decimal('f9',18,8)->nullable();
            $table->decimal('c10',18,8)->nullable();
            $table->decimal('a10',18,8)->nullable();
            $table->decimal('f10',18,8)->nullable();
            $table->decimal('c11',18,8)->nullable();
            $table->decimal('a11',18,8)->nullable();
            $table->decimal('f11',18,8)->nullable();
            $table->decimal('c12',18,8)->nullable();
            $table->decimal('a12',18,8)->nullable();
            $table->decimal('f12',18,8)->nullable();
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_anuales');
    }
};
