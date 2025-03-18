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
        Schema::create('cat_empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('rfc')->nullable();
            $table->string('curp')->nullable();
            $table->string('imss')->nullable();
            $table->decimal('sueldo',18,8)->default(0);
            $table->decimal('ret_isr',18,8)->default(0);
            $table->decimal('ret_imss',18,8)->default(0);
            $table->decimal('subsidio',18,8)->default(0);
            $table->string('estado')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_empleados');
    }
};
