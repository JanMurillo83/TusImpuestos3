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
        Schema::table('compras', function (Blueprint $table) {
            if (!Schema::hasColumn('compras', 'orden_id')) {
                $table->unsignedBigInteger('orden_id')->nullable()->after('orden');
                $table->index('orden_id');
            }
            if (!Schema::hasColumn('compras', 'requisicion_id')) {
                $table->unsignedBigInteger('requisicion_id')->nullable()->after('orden_id');
                $table->index('requisicion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (Schema::hasColumn('compras', 'orden_id')) {
                $table->dropIndex(['orden_id']);
                $table->dropColumn('orden_id');
            }
            if (Schema::hasColumn('compras', 'requisicion_id')) {
                $table->dropIndex(['requisicion_id']);
                $table->dropColumn('requisicion_id');
            }
        });
    }
};
