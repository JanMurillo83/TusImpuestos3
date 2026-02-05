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
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('clave');
            $table->string('descripcion', 1000);
            $table->integer('linea')->default(1);
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->decimal('u_costo', 18, 8)->default(0);
            $table->decimal('p_costo', 18, 8)->default(0);
            $table->decimal('precio1', 18, 8)->default(0);
            $table->decimal('precio2', 18, 8)->default(0);
            $table->decimal('precio3', 18, 8)->default(0);
            $table->decimal('precio4', 18, 8)->default(0);
            $table->decimal('precio5', 18, 8)->default(0);
            $table->decimal('exist', 18, 8)->default(0);
            $table->integer('esquema')->default(1);
            $table->string('servicio')->default('NO');
            $table->string('unidad')->nullable();
            $table->string('cvesat')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
