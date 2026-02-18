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
        Schema::table('claves', function (Blueprint $table) {
            // Índice para búsquedas por clave
            $table->index('clave');
        });

        // Índice FULLTEXT para búsquedas rápidas en descripción y mostrar
        \DB::statement('ALTER TABLE claves ADD FULLTEXT INDEX claves_fulltext_idx (descripcion, mostrar)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claves', function (Blueprint $table) {
            $table->dropIndex(['clave']);
        });

        \DB::statement('ALTER TABLE claves DROP INDEX claves_fulltext_idx');
    }
};
