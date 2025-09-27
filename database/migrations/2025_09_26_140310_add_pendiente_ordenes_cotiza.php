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
        Schema::table('ordenes_partidas', function (Blueprint $table) {
            $table->decimal('pendientes')->after('cant')->default(0);
        });
        Schema::table('cotizaciones_partidas', function (Blueprint $table) {
            $table->decimal('pendientes')->after('cant')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
