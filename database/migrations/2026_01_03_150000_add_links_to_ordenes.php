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
        Schema::table('ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes', 'requisicion_id')) {
                $table->unsignedBigInteger('requisicion_id')->nullable()->after('compra');
                $table->index('requisicion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'requisicion_id')) {
                $table->dropIndex(['requisicion_id']);
                $table->dropColumn('requisicion_id');
            }
        });
    }
};
