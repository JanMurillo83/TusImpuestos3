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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('request_id');
            $table->string('status');
            $table->string('message');
            $table->string('xml_type');
            $table->string('ini_date');
            $table->string('ini_hour');
            $table->string('end_date');
            $table->string('end_hour');
            $table->string('user_tax');
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('solicitudes_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitudes_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
