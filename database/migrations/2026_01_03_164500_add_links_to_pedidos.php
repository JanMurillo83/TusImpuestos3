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
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable()->after('uuid');
                $table->index('cotizacion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos', 'cotizacion_id')) {
                $table->dropIndex(['cotizacion_id']);
                $table->dropColumn('cotizacion_id');
            }
        });
    }
};
