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
            if (!Schema::hasColumn('pedidos', 'moneda')) {
                $table->string('moneda', 3)->default('MXN')->after('uso');
            }
            if (!Schema::hasColumn('pedidos', 'tcambio')) {
                $table->decimal('tcambio', 18, 8)->default(1)->after('moneda');
            }
        });

        Schema::table('remisiones', function (Blueprint $table) {
            if (!Schema::hasColumn('remisiones', 'moneda')) {
                $table->string('moneda', 3)->default('MXN')->after('uso');
            }
            if (!Schema::hasColumn('remisiones', 'tcambio')) {
                $table->decimal('tcambio', 18, 8)->default(1)->after('moneda');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['moneda', 'tcambio']);
        });

        Schema::table('remisiones', function (Blueprint $table) {
            $table->dropColumn(['moneda', 'tcambio']);
        });
    }
};
