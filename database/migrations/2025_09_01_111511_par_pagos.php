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
        Schema::create('par_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagos_id')->constrained();
            $table->string('uuidrel')->nullable();
            $table->string('cvesat')->nullable();
            $table->string('unisat')->nullable();
            $table->decimal('cant',18,8)->nullable();
            $table->decimal('unitario',18,8)->nullable();
            $table->decimal('importe',18,8)->nullable();
            $table->string('moneda')->nullable();
            $table->decimal('equivalencia')->default(1)->nullable();
            $table->decimal('parcialidad')->default(1)->nullable();
            $table->decimal('saldoant')->default(0)->nullable();
            $table->decimal('imppagado')->default(0)->nullable();
            $table->decimal('insoluto')->default(0)->nullable();
            $table->string('objeto')->default("02")->nullable();
            $table->decimal('tasaiva')->default(0.16)->nullable();
            $table->decimal('baseiva')->default(0)->nullable();
            $table->decimal('montoiva')->default(0)->nullable();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
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
