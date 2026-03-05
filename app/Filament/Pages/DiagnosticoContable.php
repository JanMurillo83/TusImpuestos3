<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

/**
 * Página de Diagnóstico Contable
 * Permite auditar la integridad de la información contable del tenant activo.
 */
class DiagnosticoContable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static string $view = 'filament.pages.diagnostico-contable';
    protected static ?string $navigationLabel = 'Diagnóstico Contable';
    protected static ?string $title = 'Diagnóstico Contable';
    protected static ?string $navigationGroup = 'Contabilidad';

    // Resultados del diagnóstico organizado por secciones
    public ?array $resultados = null;

    /**
     * Al montar la página se ejecuta el diagnóstico automáticamente
     */
    public function mount()
    {
        $this->ejecutarDiagnostico();
    }

    /**
     * Ejecuta todas las validaciones contables dentro del tenant actual
     */
    public function ejecutarDiagnostico()
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return;
        }
        $teamId = $tenant->id;

        $this->resultados = [
            'polizas_descuadradas' => $this->validarPolizasDescuadradas($teamId),
            'movimientos_sin_cuenta' => $this->validarMovimientosSinCuenta($teamId),
            'cuentas_duplicadas' => $this->validarCuentasDuplicadas($teamId),
            'naturaleza_incorrecta' => $this->validarNaturalezaIncorrecta($teamId),
            'movimientos_huerfanos' => $this->validarMovimientosHuerfanos($teamId),
            'problemas_acumulacion' => $this->validarProblemasAcumulacion($teamId),
            'importes_invalidos' => $this->validarImportesInvalidos($teamId),
            'jerarquia_inexistente' => $this->validarJerarquiaInexistente($teamId),
        ];
    }

    /**
     * 1. Pólizas descuadradas: SUM(cargos) != SUM(abonos)
     */
    protected function validarPolizasDescuadradas(int $teamId): array
    {
        return DB::table('auxiliares as a')
            ->join('cat_polizas as p', 'a.cat_polizas_id', '=', 'p.id')
            ->select(
                'p.id as poliza_id',
                'p.fecha',
                'p.tipo',
                'p.folio',
                'p.periodo',
                'p.ejercicio',
                DB::raw('ABS(SUM(a.cargo) - SUM(a.abono)) as diferencia')
            )
            ->where('a.team_id', $teamId)
            ->groupBy('p.id', 'p.fecha', 'p.tipo', 'p.folio', 'p.periodo', 'p.ejercicio')
            ->havingRaw('ABS(SUM(a.cargo) - SUM(a.abono)) > 0.01')
            ->get()
            ->toArray();
    }

    /**
     * 2. Movimientos sin cuenta válida: referencien cuentas inexistentes o account_id NULL
     */
    protected function validarMovimientosSinCuenta(int $teamId): array
    {
        return DB::table('auxiliares as a')
            ->leftJoin('cat_cuentas as c', function($join) use ($teamId) {
                $join->on('a.codigo', '=', 'c.codigo')
                     ->where('c.team_id', '=', $teamId);
            })
            ->join('cat_polizas as p', 'a.cat_polizas_id', '=', 'p.id')
            ->select(
                'p.id as poliza_id',
                'p.tipo',
                'p.folio',
                'p.periodo',
                'p.ejercicio',
                'a.id as movimiento_id',
                'a.codigo as cuenta_referenciada'
            )
            ->where('a.team_id', $teamId)
            ->where(function($query) {
                $query->whereNull('c.id')
                      ->orWhereNull('a.codigo')
                      ->orWhere('a.codigo', '=', '');
            })
            ->get()
            ->toArray();
    }

    /**
     * 3. Cuentas contables duplicadas: mismo código o número
     */
    protected function validarCuentasDuplicadas(int $teamId): array
    {
        return DB::table('cat_cuentas')
            ->select('codigo as cuenta', DB::raw('GROUP_CONCAT(id) as ids_duplicados'))
            ->where('team_id', $teamId)
            ->groupBy('codigo')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->toArray();
    }

    /**
     * 4. Naturaleza incorrecta: saldo final contradice naturaleza
     */
    protected function validarNaturalezaIncorrecta(int $teamId): array
    {
        // Revisamos el saldo del último periodo disponible (s12)
        return DB::table('saldoscuentas')
            ->select('codigo as cuenta', 'naturaleza', 's12 as saldo')
            ->where('team_id', $teamId)
            ->where(function($query) {
                $query->where(function($q) {
                    $q->where('naturaleza', 'D')->where('s12', '<', -0.01);
                })->orWhere(function($q) {
                    $q->where('naturaleza', 'A')->where('s12', '>', 0.01);
                });
            })
            ->get()
            ->toArray();
    }

    /**
     * 5. Movimientos huérfanos: no pertenecen a póliza válida
     */
    protected function validarMovimientosHuerfanos(int $teamId): array
    {
        return DB::table('auxiliares as a')
            ->leftJoin('cat_polizas as p', 'a.cat_polizas_id', '=', 'p.id')
            ->select('a.id', 'a.cat_polizas_id as poliza_id', 'a.codigo', 'a.cargo', 'a.abono')
            ->where('a.team_id', $teamId)
            ->whereNull('p.id')
            ->get()
            ->toArray();
    }

    /**
     * 6. Acumulación incorrecta: saldo_calculado != saldo_guardado
     */
    protected function validarProblemasAcumulacion(int $teamId): array
    {
        return DB::table('saldoscuentas')
            ->select(
                'codigo as cuenta',
                's12 as saldo_almacenado',
                DB::raw('(si + c1 + c2 + c3 + c4 + c5 + c6 + c7 + c8 + c9 + c10 + c11 + c12 - (a1 + a2 + a3 + a4 + a5 + a6 + a7 + a8 + a9 + a10 + a11 + a12)) as saldo_calculado'),
                DB::raw('ABS(s12 - (si + c1 + c2 + c3 + c4 + c5 + c6 + c7 + c8 + c9 + c10 + c11 + c12 - (a1 + a2 + a3 + a4 + a5 + a6 + a7 + a8 + a9 + a10 + a11 + a12))) as diferencia')
            )
            ->where('team_id', $teamId)
            ->havingRaw('diferencia > 0.01')
            ->get()
            ->toArray();
    }

    /**
     * 7. Movimientos con importes inválidos: (0 y 0) o (llenos ambos)
     */
    protected function validarImportesInvalidos(int $teamId): array
    {
        return DB::table('auxiliares as a')
            ->join('cat_polizas as p', 'a.cat_polizas_id', '=', 'p.id')
            ->select('a.id', 'p.id as poliza_id', 'p.tipo', 'p.folio', 'p.periodo', 'p.ejercicio', 'a.cargo', 'a.abono')
            ->where('a.team_id', $teamId)
            ->where(function($query) {
                $query->where(function($q) {
                    $q->where('a.cargo', 0)->where('a.abono', 0);
                })->orWhere(function($q) {
                    $q->where('a.cargo', '!=', 0)->where('a.abono', '!=', 0);
                });
            })
            ->get()
            ->toArray();
    }

    /**
     * 8. Cuentas inexistentes en jerarquía: cuenta hija sin padre
     */
    protected function validarJerarquiaInexistente(int $teamId): array
    {
        $cuentas = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->select('codigo', 'nombre')
            ->get();

        $inconsistencias = [];
        $codigosExistentes = $cuentas->pluck('codigo')->toArray();

        foreach ($cuentas as $cuenta) {
            // Se asume jerarquía por puntos (ej: 1101.001)
            if (str_contains($cuenta->codigo, '.')) {
                $partes = explode('.', $cuenta->codigo);
                array_pop($partes);
                $codigoPadre = implode('.', $partes);

                if (!empty($codigoPadre) && !in_array($codigoPadre, $codigosExistentes)) {
                    $inconsistencias[] = (object)[
                        'cuenta' => $cuenta->codigo,
                        'nombre' => $cuenta->nombre,
                        'padre_esperado' => $codigoPadre
                    ];
                }
            }
        }

        return $inconsistencias;
    }
}
