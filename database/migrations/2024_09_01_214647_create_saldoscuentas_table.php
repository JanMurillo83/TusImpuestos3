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
        Schema::create('saldoscuentas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('n1');
            $table->string('n2');
            $table->string('n3');
            $table->string('n4');
            $table->string('n5');
            $table->string('n6');
            $table->decimal('si', 18, 8)->default(0);
            $table->decimal('c1', 18, 8)->default(0);
            $table->decimal('c2', 18, 8)->default(0);
            $table->decimal('c3', 18, 8)->default(0);
            $table->decimal('c4', 18, 8)->default(0);
            $table->decimal('c5', 18, 8)->default(0);
            $table->decimal('c6', 18, 8)->default(0);
            $table->decimal('c7', 18, 8)->default(0);
            $table->decimal('c8', 18, 8)->default(0);
            $table->decimal('c9', 18, 8)->default(0);
            $table->decimal('c10', 18, 8)->default(0);
            $table->decimal('c11', 18, 8)->default(0);
            $table->decimal('c12', 18, 8)->default(0);
            $table->decimal('a1', 18, 8)->default(0);
            $table->decimal('a2', 18, 8)->default(0);
            $table->decimal('a3', 18, 8)->default(0);
            $table->decimal('a4', 18, 8)->default(0);
            $table->decimal('a5', 18, 8)->default(0);
            $table->decimal('a6', 18, 8)->default(0);
            $table->decimal('a7', 18, 8)->default(0);
            $table->decimal('a8', 18, 8)->default(0);
            $table->decimal('a9', 18, 8)->default(0);
            $table->decimal('a10', 18, 8)->default(0);
            $table->decimal('a11', 18, 8)->default(0);
            $table->decimal('a12', 18, 8)->default(0);
            $table->decimal('s1', 18, 8)->default(0);
            $table->decimal('s2', 18, 8)->default(0);
            $table->decimal('s3', 18, 8)->default(0);
            $table->decimal('s4', 18, 8)->default(0);
            $table->decimal('s5', 18, 8)->default(0);
            $table->decimal('s6', 18, 8)->default(0);
            $table->decimal('s7', 18, 8)->default(0);
            $table->decimal('s8', 18, 8)->default(0);
            $table->decimal('s9', 18, 8)->default(0);
            $table->decimal('s10', 18, 8)->default(0);
            $table->decimal('s11', 18, 8)->default(0);
            $table->decimal('s12', 18, 8)->default(0);
            $table->string('naturaleza')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('saldoscuentas_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldoscuentas_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldoscuentas');
    }
};
