<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices adicionales para tablas más críticas

        if (Schema::hasTable('saldoscuentas')) {
            Schema::table('saldoscuentas', function (Blueprint $table) {
                if (!$this->indexExists('saldoscuentas', 'salcue_team_codigo_idx')) {
                    $table->index(['team_id', 'codigo'], 'salcue_team_codigo_idx');
                }
                if (!$this->indexExists('saldoscuentas', 'salcue_codigo_idx')) {
                    $table->index(['codigo'], 'salcue_codigo_idx');
                }
                if (!$this->indexExists('saldoscuentas', 'salcue_n1_team_idx')) {
                    $table->index(['n1', 'team_id'], 'salcue_n1_team_idx');
                }
                if (!$this->indexExists('saldoscuentas', 'salcue_team_ejercicio_idx')) {
                    $table->index(['team_id', 'ejercicio'], 'salcue_team_ejercicio_idx');
                }
            });
        }

        if (Schema::hasTable('cat_cuentas')) {
            Schema::table('cat_cuentas', function (Blueprint $table) {
                if (!$this->indexExists('cat_cuentas', 'catcue_team_codigo_idx')) {
                    $table->index(['team_id', 'codigo'], 'catcue_team_codigo_idx');
                }
                if (!$this->indexExists('cat_cuentas', 'catcue_codigo_idx')) {
                    $table->index(['codigo'], 'catcue_codigo_idx');
                }
            });
        }

        if (Schema::hasTable('cat_polizas')) {
            Schema::table('cat_polizas', function (Blueprint $table) {
                if (!$this->indexExists('cat_polizas', 'catpol_team_per_eje_idx')) {
                    $table->index(['team_id', 'periodo', 'ejercicio'], 'catpol_team_per_eje_idx');
                }
                if (!$this->indexExists('cat_polizas', 'catpol_folio_idx')) {
                    $table->index(['folio'], 'catpol_folio_idx');
                }
            });
        }

        if (Schema::hasTable('almacencfdis')) {
            Schema::table('almacencfdis', function (Blueprint $table) {
                if (!$this->indexExists('almacencfdis', 'almcfdi_uuid_idx')) {
                    $table->index(['uuid'], 'almcfdi_uuid_idx');
                }
                if (!$this->indexExists('almacencfdis', 'almcfdi_team_fecha_idx')) {
                    $table->index(['team_id', 'fecha'], 'almcfdi_team_fecha_idx');
                }
                if (!$this->indexExists('almacencfdis', 'almcfdi_receptor_idx')) {
                    $table->index(['receptor_rfc'], 'almcfdi_receptor_idx');
                }
            });
        }

        if (Schema::hasTable('movbancos')) {
            Schema::table('movbancos', function (Blueprint $table) {
                if (!$this->indexExists('movbancos', 'movban_team_cuenta_idx')) {
                    $table->index(['team_id', 'cuenta'], 'movban_team_cuenta_idx');
                }
                if (!$this->indexExists('movbancos', 'movban_fecha_idx')) {
                    $table->index(['fecha'], 'movban_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('inventario')) {
            Schema::table('inventario', function (Blueprint $table) {
                if (!$this->indexExists('inventario', 'inv_team_clave_idx')) {
                    $table->index(['team_id', 'clave'], 'inv_team_clave_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('saldoscuentas')) {
            Schema::table('saldoscuentas', function (Blueprint $table) {
                $table->dropIndex('salcue_team_codigo_idx');
                $table->dropIndex('salcue_codigo_idx');
                $table->dropIndex('salcue_n1_team_idx');
                $table->dropIndex('salcue_team_ejercicio_idx');
            });
        }

        if (Schema::hasTable('cat_cuentas')) {
            Schema::table('cat_cuentas', function (Blueprint $table) {
                $table->dropIndex('catcue_team_codigo_idx');
                $table->dropIndex('catcue_codigo_idx');
            });
        }

        if (Schema::hasTable('cat_polizas')) {
            Schema::table('cat_polizas', function (Blueprint $table) {
                $table->dropIndex('catpol_team_per_eje_idx');
                $table->dropIndex('catpol_folio_idx');
            });
        }

        if (Schema::hasTable('almacencfdis')) {
            Schema::table('almacencfdis', function (Blueprint $table) {
                $table->dropIndex('almcfdi_uuid_idx');
                $table->dropIndex('almcfdi_team_fecha_idx');
                $table->dropIndex('almcfdi_receptor_idx');
            });
        }

        if (Schema::hasTable('movbancos')) {
            Schema::table('movbancos', function (Blueprint $table) {
                $table->dropIndex('movban_team_cuenta_idx');
                $table->dropIndex('movban_fecha_idx');
            });
        }

        if (Schema::hasTable('inventario')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->dropIndex('inv_team_clave_idx');
            });
        }
    }

    private function indexExists($table, $index): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
