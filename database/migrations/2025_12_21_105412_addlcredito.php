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
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('limite_credito',18,8)->default(0)->after('saldo');
        });
        Schema::table('proveedores', function (Blueprint $table) {
            $table->decimal('limite_credito',18,8)->default(0)->after('saldo');
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
