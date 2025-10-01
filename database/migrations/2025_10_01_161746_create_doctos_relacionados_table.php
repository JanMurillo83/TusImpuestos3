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
        Schema::create('doctos_relacionados', function (Blueprint $table) {
            $table->id();
            $table->string('docto_type')->nullable();
            $table->integer('docto_id');
            $table->integer('rel_id');
            $table->string('rel_type')->nullable();
            $table->string('rel_cause')->nullable();
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctos_relacionados');
    }
};
