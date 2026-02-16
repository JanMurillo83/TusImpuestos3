<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 3: Tablas para monitoreo, métricas y auditoría
     */
    public function up(): void
    {
        // Tabla de métricas de performance
        Schema::create('saldos_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('metric_type', 50); // job_execution, cache_hit, query_time, etc.
            $table->string('metric_name', 100);
            $table->decimal('value', 15, 4);
            $table->string('unit', 20)->nullable(); // ms, count, percentage, etc.
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['team_id', 'metric_type', 'recorded_at']);
            $table->index('recorded_at');
        });

        // Tabla de health checks
        Schema::create('saldos_health_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('check_type', 50); // consistency, performance, data_integrity
            $table->string('status', 20); // pass, fail, warning
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['team_id', 'status', 'checked_at']);
            $table->index('checked_at');
        });

        // Tabla de auditoría de cambios en saldos
        Schema::create('saldos_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('codigo', 20);
            $table->string('field_changed', 50)->default('final'); // Campo que cambió
            $table->string('action', 20); // created, updated, recalculated
            $table->decimal('old_value', 18, 8)->nullable();
            $table->decimal('new_value', 18, 8);
            $table->decimal('difference', 18, 8)->nullable();
            $table->string('triggered_by', 50); // job, manual, system
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'codigo', 'created_at']);
            $table->index('created_at');
        });

        // Tabla de patrones de uso (para precarga inteligente)
        Schema::create('saldos_usage_patterns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('resource_type', 50); // dashboard, report, account
            $table->string('resource_id', 100);
            $table->integer('access_count')->default(1);
            $table->timestamp('last_accessed_at');
            $table->integer('ejercicio');
            $table->integer('periodo');
            $table->time('typical_access_time')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'resource_type', 'resource_id', 'ejercicio', 'periodo'], 'unique_usage_pattern');
            $table->index(['team_id', 'access_count']);
        });

        // Tabla de alertas y notificaciones
        Schema::create('saldos_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('alert_type', 50); // performance_degradation, data_inconsistency, etc.
            $table->string('severity', 20); // info, warning, error, critical
            $table->string('title', 200);
            $table->text('message');
            $table->json('details')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable(); // Fecha de resolución
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'severity', 'acknowledged']);
            $table->index('created_at');
        });

        // Tabla de jobs history extendida
        Schema::create('saldos_job_history', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 50);
            $table->unsignedBigInteger('team_id');
            $table->string('codigo', 20);
            $table->integer('ejercicio');
            $table->integer('periodo');
            $table->string('status', 20); // queued, processing, completed, failed
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('attempts')->default(1);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status', 'created_at']);
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_job_history');
        Schema::dropIfExists('saldos_alerts');
        Schema::dropIfExists('saldos_usage_patterns');
        Schema::dropIfExists('saldos_audit_log');
        Schema::dropIfExists('saldos_health_checks');
        Schema::dropIfExists('saldos_metrics');
    }
};
