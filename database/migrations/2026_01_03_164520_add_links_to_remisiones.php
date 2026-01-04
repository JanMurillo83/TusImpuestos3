<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remisiones', function (Blueprint $table) {
            if (!Schema::hasColumn('remisiones', 'pedido_id')) {
                $table->unsignedBigInteger('pedido_id')->nullable()->after('uuid');
                $table->index('pedido_id');
            }
            if (!Schema::hasColumn('remisiones', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable()->after('pedido_id');
                $table->index('cotizacion_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('remisiones', function (Blueprint $table) {
            if (Schema::hasColumn('remisiones', 'pedido_id')) {
                $table->dropIndex(['pedido_id']);
                $table->dropColumn('pedido_id');
            }
            if (Schema::hasColumn('remisiones', 'cotizacion_id')) {
                $table->dropIndex(['cotizacion_id']);
                $table->dropColumn('cotizacion_id');
            }
        });
    }
};
