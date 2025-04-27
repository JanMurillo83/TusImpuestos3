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
        Schema::create('usd_movs', function (Blueprint $table) {
            $table->id();
            $table->integer('xml_id');
            $table->integer('poliza')->nullable();
            $table->decimal('subtotalusd',18,8)->default(0);
            $table->decimal('ivausd',18,8)->default(0);
            $table->decimal('totalusd',18,8)->default(0);
            $table->decimal('subtotalmxn',18,8)->default(0);
            $table->decimal('ivamxn',18,8)->default(0);
            $table->decimal('totalmxn',18,8)->default(0);
            $table->decimal('tcambio',18,8)->default(0);
            $table->string('uuid')->nullable();
            $table->string( 'referencia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usd_movs');
    }
};
