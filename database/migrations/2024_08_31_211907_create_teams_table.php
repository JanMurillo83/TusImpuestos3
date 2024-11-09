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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('taxid');
            $table->string('archivokey')->nullable();
            $table->string('archivocer')->nullable();
            $table->integer('periodo')->nullable();
            $table->integer('ejercicio')->nullable();
            $table->string('fielpass')->nullable();
            $table->string('regimen')->nullable();
            $table->string('codigopos')->nullable();
            $table->string('csdkey')->nullable();
            $table->string('csdcer')->nullable();
            $table->string('csdpass')->nullable();
            $table->timestamps();
        });
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('team_user');
    }
};
