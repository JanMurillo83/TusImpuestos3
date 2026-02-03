<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auxiliares')) {
            Schema::table('auxiliares', function (Blueprint $table) {
                if (! $this->indexExists('auxiliares', 'aux_team_codigo_idx')) {
                    $table->index(['team_id', 'codigo'], 'aux_team_codigo_idx');
                }
                if (! $this->indexExists('auxiliares', 'aux_poliza_codigo_idx')) {
                    $table->index(['cat_polizas_id', 'codigo'], 'aux_poliza_codigo_idx');
                }
                if (! $this->indexExists('auxiliares', 'aux_igeg_idx')) {
                    $table->index('igeg_id', 'aux_igeg_idx');
                }
            });
            $this->addAuxFacturaIndex();
        }

        if (Schema::hasTable('cat_polizas')) {
            Schema::table('cat_polizas', function (Blueprint $table) {
                if (! $this->indexExists('cat_polizas', 'catpol_team_tipo_per_eje_fol_idx')) {
                    $table->index(['team_id', 'tipo', 'periodo', 'ejercicio', 'folio'], 'catpol_team_tipo_per_eje_fol_idx');
                }
                if (! $this->indexExists('cat_polizas', 'catpol_team_fecha_idx')) {
                    $table->index(['team_id', 'fecha'], 'catpol_team_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('cat_cuentas')) {
            Schema::table('cat_cuentas', function (Blueprint $table) {
                if (! $this->indexExists('cat_cuentas', 'catcuentas_team_codigo_idx')) {
                    $table->index(['team_id', 'codigo'], 'catcuentas_team_codigo_idx');
                }
                if (! $this->indexExists('cat_cuentas', 'catcuentas_team_acumula_idx')) {
                    $table->index(['team_id', 'acumula'], 'catcuentas_team_acumula_idx');
                }
                if (! $this->indexExists('cat_cuentas', 'catcuentas_team_tipo_codigo_idx')) {
                    $table->index(['team_id', 'tipo', 'codigo'], 'catcuentas_team_tipo_codigo_idx');
                }
            });
        }

        if (Schema::hasTable('almacencfdis')) {
            Schema::table('almacencfdis', function (Blueprint $table) {
                if (! $this->indexExists('almacencfdis', 'alm_team_uuid_idx')) {
                    $table->index(['team_id', 'UUID'], 'alm_team_uuid_idx');
                }
                if (! $this->indexExists('almacencfdis', 'alm_team_xml_tipo_per_eje_idx')) {
                    $table->index(['team_id', 'xml_type', 'TipoDeComprobante', 'periodo', 'ejercicio'], 'alm_team_xml_tipo_per_eje_idx');
                }
                if (! $this->indexExists('almacencfdis', 'alm_team_used_idx')) {
                    $table->index(['team_id', 'used'], 'alm_team_used_idx');
                }
                if (! $this->indexExists('almacencfdis', 'alm_team_emisor_rfc_idx')) {
                    $table->index(['team_id', 'Emisor_Rfc'], 'alm_team_emisor_rfc_idx');
                }
                if (! $this->indexExists('almacencfdis', 'alm_team_receptor_rfc_idx')) {
                    $table->index(['team_id', 'Receptor_Rfc'], 'alm_team_receptor_rfc_idx');
                }
            });
        }

        if (Schema::hasTable('ingresos_egresos')) {
            Schema::table('ingresos_egresos', function (Blueprint $table) {
                if (! $this->indexExists('ingresos_egresos', 'ingeg_team_tipo_pend_idx')) {
                    $table->index(['team_id', 'tipo', 'pendientemxn'], 'ingeg_team_tipo_pend_idx');
                }
                if (! $this->indexExists('ingresos_egresos', 'ingeg_xml_id_idx')) {
                    $table->index('xml_id', 'ingeg_xml_id_idx');
                }
                if (! $this->indexExists('ingresos_egresos', 'ingeg_team_per_eje_idx')) {
                    $table->index(['team_id', 'periodo', 'ejercicio'], 'ingeg_team_per_eje_idx');
                }
            });
        }

        if (Schema::hasTable('movbancos')) {
            Schema::table('movbancos', function (Blueprint $table) {
                if (! $this->indexExists('movbancos', 'movb_team_eje_per_cta_tipo_idx')) {
                    $table->index(['team_id', 'ejercicio', 'periodo', 'cuenta', 'tipo'], 'movb_team_eje_per_cta_tipo_idx');
                }
                if (! $this->indexExists('movbancos', 'movb_team_fecha_idx')) {
                    $table->index(['team_id', 'fecha'], 'movb_team_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('facturas')) {
            Schema::table('facturas', function (Blueprint $table) {
                if (! $this->indexExists('facturas', 'fact_team_fecha_idx')) {
                    $table->index(['team_id', 'fecha'], 'fact_team_fecha_idx');
                }
                if (! $this->indexExists('facturas', 'fact_team_clie_idx')) {
                    $table->index(['team_id', 'clie'], 'fact_team_clie_idx');
                }
                if (! $this->indexExists('facturas', 'fact_team_uuid_idx')) {
                    $table->index(['team_id', 'uuid'], 'fact_team_uuid_idx');
                }
            });
        }

        if (Schema::hasTable('facturas_partidas')) {
            Schema::table('facturas_partidas', function (Blueprint $table) {
                if (! $this->indexExists('facturas_partidas', 'factpar_facturas_id_idx')) {
                    $table->index('facturas_id', 'factpar_facturas_id_idx');
                }
            });
        }

        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table) {
                if (! $this->indexExists('compras', 'compras_team_fecha_idx')) {
                    $table->index(['team_id', 'fecha'], 'compras_team_fecha_idx');
                }
                if (! $this->indexExists('compras', 'compras_team_prov_idx')) {
                    $table->index(['team_id', 'prov'], 'compras_team_prov_idx');
                }
            });
        }

        if (Schema::hasTable('compras_partidas')) {
            Schema::table('compras_partidas', function (Blueprint $table) {
                if (! $this->indexExists('compras_partidas', 'compraspar_compras_id_idx')) {
                    $table->index('compras_id', 'compraspar_compras_id_idx');
                }
            });
        }

        if (Schema::hasTable('inventarios')) {
            Schema::table('inventarios', function (Blueprint $table) {
                if (! $this->indexExists('inventarios', 'inv_team_clave_idx')) {
                    $table->index(['team_id', 'clave'], 'inv_team_clave_idx');
                }
                if (! $this->indexExists('inventarios', 'inv_team_linea_idx')) {
                    $table->index(['team_id', 'linea'], 'inv_team_linea_idx');
                }
            });
        }

        if (Schema::hasTable('movinventarios')) {
            Schema::table('movinventarios', function (Blueprint $table) {
                if (! $this->indexExists('movinventarios', 'movinv_team_prod_fecha_idx')) {
                    $table->index(['team_id', 'producto', 'fecha'], 'movinv_team_prod_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('conta_periodos')) {
            Schema::table('conta_periodos', function (Blueprint $table) {
                if (! $this->indexExists('conta_periodos', 'conta_team_per_eje_idx')) {
                    $table->index(['team_id', 'periodo', 'ejercicio'], 'conta_team_per_eje_idx');
                }
            });
        }

        if (Schema::hasTable('temp_cfdis')) {
            Schema::table('temp_cfdis', function (Blueprint $table) {
                if (! $this->indexExists('temp_cfdis', 'tempcfdi_team_uuid_idx')) {
                    $table->index(['team_id', 'UUID'], 'tempcfdi_team_uuid_idx');
                }
            });
        }

        if (Schema::hasTable('series_facturas')) {
            Schema::table('series_facturas', function (Blueprint $table) {
                if (! $this->indexExists('series_facturas', 'series_team_tipo_serie_idx')) {
                    $table->index(['team_id', 'tipo', 'serie'], 'series_team_tipo_serie_idx');
                }
            });
        }

        if (Schema::hasTable('terceros')) {
            Schema::table('terceros', function (Blueprint $table) {
                if (! $this->indexExists('terceros', 'terceros_team_rfc_idx')) {
                    $table->index(['team_id', 'rfc'], 'terceros_team_rfc_idx');
                }
                if (! $this->indexExists('terceros', 'terceros_team_tipo_idx')) {
                    $table->index(['team_id', 'tipo'], 'terceros_team_tipo_idx');
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (! $this->indexExists('clientes', 'clientes_team_rfc_idx')) {
                    $table->index(['team_id', 'rfc'], 'clientes_team_rfc_idx');
                }
            });
        }

        if (Schema::hasTable('proveedores')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if (! $this->indexExists('proveedores', 'proveedores_team_rfc_idx')) {
                    $table->index(['team_id', 'rfc'], 'proveedores_team_rfc_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auxiliares')) {
            Schema::table('auxiliares', function (Blueprint $table) {
                if ($this->indexExists('auxiliares', 'aux_team_codigo_idx')) {
                    $table->dropIndex('aux_team_codigo_idx');
                }
                if ($this->indexExists('auxiliares', 'aux_poliza_codigo_idx')) {
                    $table->dropIndex('aux_poliza_codigo_idx');
                }
                if ($this->indexExists('auxiliares', 'aux_igeg_idx')) {
                    $table->dropIndex('aux_igeg_idx');
                }
                if ($this->indexExists('auxiliares', 'aux_factura_team_idx')) {
                    $table->dropIndex('aux_factura_team_idx');
                }
            });
        }

        if (Schema::hasTable('cat_polizas')) {
            Schema::table('cat_polizas', function (Blueprint $table) {
                if ($this->indexExists('cat_polizas', 'catpol_team_tipo_per_eje_fol_idx')) {
                    $table->dropIndex('catpol_team_tipo_per_eje_fol_idx');
                }
                if ($this->indexExists('cat_polizas', 'catpol_team_fecha_idx')) {
                    $table->dropIndex('catpol_team_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('cat_cuentas')) {
            Schema::table('cat_cuentas', function (Blueprint $table) {
                if ($this->indexExists('cat_cuentas', 'catcuentas_team_codigo_idx')) {
                    $table->dropIndex('catcuentas_team_codigo_idx');
                }
                if ($this->indexExists('cat_cuentas', 'catcuentas_team_acumula_idx')) {
                    $table->dropIndex('catcuentas_team_acumula_idx');
                }
                if ($this->indexExists('cat_cuentas', 'catcuentas_team_tipo_codigo_idx')) {
                    $table->dropIndex('catcuentas_team_tipo_codigo_idx');
                }
            });
        }

        if (Schema::hasTable('almacencfdis')) {
            Schema::table('almacencfdis', function (Blueprint $table) {
                if ($this->indexExists('almacencfdis', 'alm_team_uuid_idx')) {
                    $table->dropIndex('alm_team_uuid_idx');
                }
                if ($this->indexExists('almacencfdis', 'alm_team_xml_tipo_per_eje_idx')) {
                    $table->dropIndex('alm_team_xml_tipo_per_eje_idx');
                }
                if ($this->indexExists('almacencfdis', 'alm_team_used_idx')) {
                    $table->dropIndex('alm_team_used_idx');
                }
                if ($this->indexExists('almacencfdis', 'alm_team_emisor_rfc_idx')) {
                    $table->dropIndex('alm_team_emisor_rfc_idx');
                }
                if ($this->indexExists('almacencfdis', 'alm_team_receptor_rfc_idx')) {
                    $table->dropIndex('alm_team_receptor_rfc_idx');
                }
            });
        }

        if (Schema::hasTable('ingresos_egresos')) {
            Schema::table('ingresos_egresos', function (Blueprint $table) {
                if ($this->indexExists('ingresos_egresos', 'ingeg_team_tipo_pend_idx')) {
                    $table->dropIndex('ingeg_team_tipo_pend_idx');
                }
                if ($this->indexExists('ingresos_egresos', 'ingeg_xml_id_idx')) {
                    $table->dropIndex('ingeg_xml_id_idx');
                }
                if ($this->indexExists('ingresos_egresos', 'ingeg_team_per_eje_idx')) {
                    $table->dropIndex('ingeg_team_per_eje_idx');
                }
            });
        }

        if (Schema::hasTable('movbancos')) {
            Schema::table('movbancos', function (Blueprint $table) {
                if ($this->indexExists('movbancos', 'movb_team_eje_per_cta_tipo_idx')) {
                    $table->dropIndex('movb_team_eje_per_cta_tipo_idx');
                }
                if ($this->indexExists('movbancos', 'movb_team_fecha_idx')) {
                    $table->dropIndex('movb_team_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('facturas')) {
            Schema::table('facturas', function (Blueprint $table) {
                if ($this->indexExists('facturas', 'fact_team_fecha_idx')) {
                    $table->dropIndex('fact_team_fecha_idx');
                }
                if ($this->indexExists('facturas', 'fact_team_clie_idx')) {
                    $table->dropIndex('fact_team_clie_idx');
                }
                if ($this->indexExists('facturas', 'fact_team_uuid_idx')) {
                    $table->dropIndex('fact_team_uuid_idx');
                }
            });
        }

        if (Schema::hasTable('facturas_partidas')) {
            Schema::table('facturas_partidas', function (Blueprint $table) {
                if ($this->indexExists('facturas_partidas', 'factpar_facturas_id_idx')) {
                    $table->dropIndex('factpar_facturas_id_idx');
                }
            });
        }

        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table) {
                if ($this->indexExists('compras', 'compras_team_fecha_idx')) {
                    $table->dropIndex('compras_team_fecha_idx');
                }
                if ($this->indexExists('compras', 'compras_team_prov_idx')) {
                    $table->dropIndex('compras_team_prov_idx');
                }
            });
        }

        if (Schema::hasTable('compras_partidas')) {
            Schema::table('compras_partidas', function (Blueprint $table) {
                if ($this->indexExists('compras_partidas', 'compraspar_compras_id_idx')) {
                    $table->dropIndex('compraspar_compras_id_idx');
                }
            });
        }

        if (Schema::hasTable('inventarios')) {
            Schema::table('inventarios', function (Blueprint $table) {
                if ($this->indexExists('inventarios', 'inv_team_clave_idx')) {
                    $table->dropIndex('inv_team_clave_idx');
                }
                if ($this->indexExists('inventarios', 'inv_team_linea_idx')) {
                    $table->dropIndex('inv_team_linea_idx');
                }
            });
        }

        if (Schema::hasTable('movinventarios')) {
            Schema::table('movinventarios', function (Blueprint $table) {
                if ($this->indexExists('movinventarios', 'movinv_team_prod_fecha_idx')) {
                    $table->dropIndex('movinv_team_prod_fecha_idx');
                }
            });
        }

        if (Schema::hasTable('conta_periodos')) {
            Schema::table('conta_periodos', function (Blueprint $table) {
                if ($this->indexExists('conta_periodos', 'conta_team_per_eje_idx')) {
                    $table->dropIndex('conta_team_per_eje_idx');
                }
            });
        }

        if (Schema::hasTable('temp_cfdis')) {
            Schema::table('temp_cfdis', function (Blueprint $table) {
                if ($this->indexExists('temp_cfdis', 'tempcfdi_team_uuid_idx')) {
                    $table->dropIndex('tempcfdi_team_uuid_idx');
                }
            });
        }

        if (Schema::hasTable('series_facturas')) {
            Schema::table('series_facturas', function (Blueprint $table) {
                if ($this->indexExists('series_facturas', 'series_team_tipo_serie_idx')) {
                    $table->dropIndex('series_team_tipo_serie_idx');
                }
            });
        }

        if (Schema::hasTable('terceros')) {
            Schema::table('terceros', function (Blueprint $table) {
                if ($this->indexExists('terceros', 'terceros_team_rfc_idx')) {
                    $table->dropIndex('terceros_team_rfc_idx');
                }
                if ($this->indexExists('terceros', 'terceros_team_tipo_idx')) {
                    $table->dropIndex('terceros_team_tipo_idx');
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if ($this->indexExists('clientes', 'clientes_team_rfc_idx')) {
                    $table->dropIndex('clientes_team_rfc_idx');
                }
            });
        }

        if (Schema::hasTable('proveedores')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if ($this->indexExists('proveedores', 'proveedores_team_rfc_idx')) {
                    $table->dropIndex('proveedores_team_rfc_idx');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $database = $connection->getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return $result && (int) $result->count > 0;
    }

    private function addAuxFacturaIndex(): void
    {
        if ($this->indexExists('auxiliares', 'aux_factura_team_idx')) {
            return;
        }

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('CREATE INDEX aux_factura_team_idx ON auxiliares (factura(191), team_id)');
            return;
        }

        Schema::table('auxiliares', function (Blueprint $table) {
            $table->index(['factura', 'team_id'], 'aux_factura_team_idx');
        });
    }
};
