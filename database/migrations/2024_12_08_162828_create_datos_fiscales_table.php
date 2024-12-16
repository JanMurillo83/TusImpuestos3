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
        Schema::create('datos_fiscales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('rfc')->nullable();
            $table->string('regimen')->nullable();
            $table->string('codigo')->nullable();
            $table->text('cer')->nullable();
            $table->text('key')->nullable();
            $table->string('csdpass')->nullable();
            $table->text('logo')->nullable();
            $table->longText('logo64')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datos_fiscales');
    }
};
