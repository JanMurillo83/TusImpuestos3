<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_drafts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cotizacion_id')->nullable();
            $table->string('draft_key', 120)->unique();
            $table->longText('payload')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'user_id']);
            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_drafts');
    }
};
