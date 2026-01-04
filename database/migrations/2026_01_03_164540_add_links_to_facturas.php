<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'remision_id')) {
                $table->unsignedBigInteger('remision_id')->nullable()->after('uuid');
                $table->index('remision_id');
            }
            if (!Schema::hasColumn('facturas', 'pedido_id')) {
                $table->unsignedBigInteger('pedido_id')->nullable()->after('remision_id');
                $table->index('pedido_id');
            }
            if (!Schema::hasColumn('facturas', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable()->after('pedido_id');
                $table->index('cotizacion_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'remision_id')) {
                $table->dropIndex(['remision_id']);
                $table->dropColumn('remision_id');
            }
            if (Schema::hasColumn('facturas', 'pedido_id')) {
                $table->dropIndex(['pedido_id']);
                $table->dropColumn('pedido_id');
            }
            if (Schema::hasColumn('facturas', 'cotizacion_id')) {
                $table->dropIndex(['cotizacion_id']);
                $table->dropColumn('cotizacion_id');
            }
        });
    }
};
