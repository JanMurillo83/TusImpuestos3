<?php

namespace App\Filament\Pages;

use App\Services\SaldosService;
use App\Services\SaldosCache;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecontabilizarSaldos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Recontabilizar Saldos';
    protected static ?string $title = 'Recontabilizar Saldos Contables';
    protected static ?string $navigationGroup = 'Herramientas';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.recontabilizar-saldos';

    public ?array $data = [];
    public array $stats = [];
    public bool $isProcessing = false;
    public ?array $ultimoInforme = null;

    public function mount(): void
    {
        $this->form->fill([
            'ejercicio' => null,
            'periodo' => null,
            'recalcular_jerarquia' => true,
            'limpiar_cache' => true,
            'validar_despues' => true,
        ]);

        $this->loadStats();
    }

    protected function getFormSchema(): array
    {
        $team = Filament::getTenant();

        // Obtener ejercicios disponibles de auxiliares
        $ejercicios = DB::table('auxiliares')
            ->select('a_ejercicio')
            ->where('team_id', $team->id)
            ->distinct()
            ->orderBy('a_ejercicio', 'desc')
            ->pluck('a_ejercicio', 'a_ejercicio')
            ->toArray();

        // Obtener periodos disponibles
        $periodos = [];
        for ($i = 1; $i <= 13; $i++) {
            $periodos[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        return [
            Section::make('Selección de Periodo')
                ->description('Selecciona el ejercicio y periodo a recontabilizar. Deja en blanco para recontabilizar TODO.')
                ->schema([
                    Select::make('ejercicio')
                        ->label('Ejercicio')
                        ->options($ejercicios)
                        ->placeholder('Todos los ejercicios')
                        ->helperText('Dejar vacío para recontabilizar todos los ejercicios'),

                    Select::make('periodo')
                        ->label('Periodo')
                        ->options($periodos)
                        ->placeholder('Todos los periodos')
                        ->helperText('Dejar vacío para recontabilizar todos los periodos del ejercicio'),
                ]),

            Section::make('Opciones de Recálculo')
                ->schema([
                    Checkbox::make('recalcular_jerarquia')
                        ->label('Recalcular jerarquía de cuentas padre')
                        ->helperText('Actualiza los saldos de las cuentas de mayor nivel (recomendado)')
                        ->default(true),

                    Checkbox::make('limpiar_cache')
                        ->label('Limpiar cache después del recálculo')
                        ->helperText('Limpia el cache de saldos para forzar recarga (recomendado)')
                        ->default(true),

                    Checkbox::make('validar_despues')
                        ->label('Validar integridad después del recálculo')
                        ->helperText('Ejecuta validaciones de consistencia al finalizar')
                        ->default(true),
                ]),
        ];
    }

    public function recontabilizar(): void
    {
        $this->isProcessing = true;

        $data = $this->form->getState();
        $team = Filament::getTenant();

        try {
            DB::beginTransaction();

            $ejercicio = $data['ejercicio'] ?? null;
            $periodo = $data['periodo'] ?? null;
            $recalcularJerarquia = $data['recalcular_jerarquia'] ?? true;
            $limpiarCache = $data['limpiar_cache'] ?? true;
            $validarDespues = $data['validar_despues'] ?? true;

            // Log de inicio
            Log::info('Iniciando recontabilización manual', [
                'team_id' => $team->id,
                'ejercicio' => $ejercicio ?? 'TODOS',
                'periodo' => $periodo ?? 'TODOS',
                'user_id' => auth()->id(),
            ]);

            // Obtener lista de periodos a procesar
            $periodosAProcesar = $this->obtenerPeriodosAProcesar($team->id, $ejercicio, $periodo);

            if (empty($periodosAProcesar)) {
                Notification::make()
                    ->warning()
                    ->title('Sin datos para procesar')
                    ->body('No se encontraron periodos con movimientos para recontabilizar.')
                    ->send();

                $this->isProcessing = false;
                return;
            }

            // Contador de resultados e informe detallado
            $cuentasActualizadas = 0;
            $errores = 0;
            $informe = [
                'periodos_procesados' => [],
                'cuentas_por_periodo' => [],
                'errores_detalle' => [],
                'inicio' => now(),
            ];

            // Procesar cada periodo
            foreach ($periodosAProcesar as $periodoData) {
                $ejProc = $periodoData->ejercicio;
                $perProc = $periodoData->periodo;
                $cuentasPeriodo = 0;

                try {
                    // Obtener cuentas afectadas en este periodo
                    $cuentasAfectadas = DB::table('auxiliares')
                        ->select('codigo')
                        ->where('team_id', $team->id)
                        ->where('a_ejercicio', $ejProc)
                        ->where('a_periodo', $perProc)
                        ->distinct()
                        ->get();

                    // Recalcular cada cuenta
                    foreach ($cuentasAfectadas as $cuenta) {
                        try {
                            // Limpiar saldo existente
                            DB::table('saldos_reportes')
                                ->where('team_id', $team->id)
                                ->where('codigo', $cuenta->codigo)
                                ->delete();

                            // Recalcular desde auxiliares
                            $this->recalcularCuenta($team->id, $cuenta->codigo, $ejProc, $perProc);

                            $cuentasActualizadas++;
                            $cuentasPeriodo++;
                        } catch (\Exception $e) {
                            $errores++;
                            $informe['errores_detalle'][] = [
                                'tipo' => 'cuenta',
                                'cuenta' => $cuenta->codigo,
                                'ejercicio' => $ejProc,
                                'periodo' => $perProc,
                                'error' => $e->getMessage(),
                            ];
                            Log::error('Error al recalcular cuenta', [
                                'cuenta' => $cuenta->codigo,
                                'ejercicio' => $ejProc,
                                'periodo' => $perProc,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Registrar periodo procesado
                    $informe['periodos_procesados'][] = "{$ejProc}-{$perProc}";
                    $informe['cuentas_por_periodo']["{$ejProc}-{$perProc}"] = $cuentasPeriodo;

                } catch (\Exception $e) {
                    $errores++;
                    $informe['errores_detalle'][] = [
                        'tipo' => 'periodo',
                        'ejercicio' => $ejProc,
                        'periodo' => $perProc,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Error al procesar periodo', [
                        'ejercicio' => $ejProc,
                        'periodo' => $perProc,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Recalcular jerarquía si se requiere (después de actualizar todas las cuentas)
            if ($recalcularJerarquia) {
                $this->recalcularJerarquiaCuentas($team->id);
            }

            // Limpiar cache si se requiere
            if ($limpiarCache) {
                SaldosCache::invalidate($team->id);

                // Intentar limpiar tags solo si el driver lo soporta
                try {
                    cache()->tags(['saldos'])->flush();
                } catch (\BadMethodCallException $e) {
                    // El driver actual no soporta tags, ignorar
                }
            }

            // Validar si se requiere
            $inconsistencias = [];
            if ($validarDespues) {
                $inconsistencias = $this->validarIntegridad($team->id, $ejercicio, $periodo);
            }

            // Generar informe completo
            $informe['fin'] = now();
            $informe['duracion_segundos'] = $informe['fin']->diffInSeconds($informe['inicio']);
            $informe['resumen'] = [
                'total_periodos' => count($informe['periodos_procesados']),
                'total_cuentas' => $cuentasActualizadas,
                'total_errores' => $errores,
                'total_inconsistencias' => count($inconsistencias),
            ];
            $informe['inconsistencias'] = $inconsistencias;

            // Detectar más problemas
            $informe['cuentas_sin_catalogo'] = $this->detectarCuentasSinCatalogo($team->id);
            $informe['cuentas_sin_movimientos'] = $this->detectarCuentasSinMovimientos($team->id, $ejercicio, $periodo);

            // Guardar informe para mostrar en UI
            $this->ultimoInforme = $informe;

            // Recargar estadísticas
            $this->loadStats();

            // Notificación de éxito
            $mensaje = "Recontabilización completada:\n";
            $mensaje .= "• {$cuentasActualizadas} cuentas actualizadas\n";
            $mensaje .= "• " . count($informe['periodos_procesados']) . " periodos procesados\n";
            if ($errores > 0) {
                $mensaje .= "• {$errores} errores encontrados\n";
            }
            if (!empty($inconsistencias)) {
                $mensaje .= "• " . count($inconsistencias) . " inconsistencias detectadas\n";
            }
            $mensaje .= "\n📊 Ver informe detallado abajo";

            Notification::make()
                ->success()
                ->title('Recontabilización completada')
                ->body($mensaje)
                ->persistent()
                ->send();

            Log::info('Recontabilización completada', [
                'team_id' => $team->id,
                'cuentas_actualizadas' => $cuentasActualizadas,
                'errores' => $errores,
                'inconsistencias' => count($inconsistencias),
                'duracion_segundos' => $informe['duracion_segundos'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error en recontabilización', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error en la recontabilización')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->persistent()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
    }

    protected function recalcularCuenta(int $teamId, string $codigo, int $ejercicio, int $periodo): void
    {
        // Obtener información de la cuenta desde cat_cuentas
        $catCuenta = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->select('nombre', 'acumula', 'naturaleza')
            ->first();

        if (!$catCuenta) {
            // Si la cuenta no existe en cat_cuentas, usar valores por defecto
            $catCuenta = (object)[
                'nombre' => $codigo,
                'acumula' => 'S',
                'naturaleza' => 'D',
            ];
        }

        // Calcular nivel basado en la cantidad de segmentos del código
        // Ejemplo: "1" = nivel 1, "1.1" = nivel 2, "1.1.01" = nivel 3
        $nivel = substr_count($codigo, '.') + 1;

        // Calcular saldo anterior (acumulado de periodos anteriores en el mismo ejercicio)
        $saldoAnterior = DB::table('auxiliares')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', '<', $periodo)
            ->selectRaw('COALESCE(SUM(cargo - abono), 0) as saldo')
            ->value('saldo') ?? 0;

        // Calcular movimientos del periodo actual
        $movimientos = DB::table('auxiliares')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', $periodo)
            ->selectRaw('
                COALESCE(SUM(cargo), 0) as cargos,
                COALESCE(SUM(abono), 0) as abonos
            ')
            ->first();

        $cargos = $movimientos->cargos ?? 0;
        $abonos = $movimientos->abonos ?? 0;
        $saldoFinal = $saldoAnterior + $cargos - $abonos;

        // Insertar o actualizar en saldos_reportes
        DB::table('saldos_reportes')->updateOrInsert(
            [
                'team_id' => $teamId,
                'codigo' => $codigo,
            ],
            [
                'cuenta' => $catCuenta->nombre,
                'acumula' => $catCuenta->acumula,
                'naturaleza' => $catCuenta->naturaleza,
                'nivel' => $nivel,
                'anterior' => $saldoAnterior,
                'cargos' => $cargos,
                'abonos' => $abonos,
                'final' => $saldoFinal,
                'updated_at' => now(),
            ]
        );
    }

    protected function obtenerPeriodosAProcesar(int $teamId, ?int $ejercicio, ?int $periodo): array
    {
        $query = DB::table('auxiliares')
            ->select('a_ejercicio as ejercicio', 'a_periodo as periodo')
            ->where('team_id', $teamId)
            ->distinct();

        if ($ejercicio) {
            $query->where('a_ejercicio', $ejercicio);
        }

        if ($periodo) {
            $query->where('a_periodo', $periodo);
        }

        return $query->orderBy('ejercicio')->orderBy('periodo')->get()->toArray();
    }

    protected function validarIntegridad(int $teamId, ?int $ejercicio, ?int $periodo): array
    {
        // Validar que los saldos en saldos_reportes coincidan con los calculados desde auxiliares
        $query = "
            SELECT
                sr.codigo,
                sr.anterior,
                sr.cargos,
                sr.abonos,
                sr.final,
                COALESCE(SUM(a.cargo), 0) as cargos_reales,
                COALESCE(SUM(a.abono), 0) as abonos_reales
            FROM saldos_reportes sr
            LEFT JOIN auxiliares a ON a.team_id = sr.team_id AND a.codigo = sr.codigo
            WHERE sr.team_id = ?
        ";

        $params = [$teamId];

        if ($ejercicio) {
            $query .= " AND a.a_ejercicio = ?";
            $params[] = $ejercicio;
        }

        if ($periodo) {
            $query .= " AND a.a_periodo = ?";
            $params[] = $periodo;
        }

        $query .= " GROUP BY sr.codigo, sr.anterior, sr.cargos, sr.abonos, sr.final
                    HAVING ABS(sr.cargos - cargos_reales) > 0.01 OR ABS(sr.abonos - abonos_reales) > 0.01";

        return DB::select($query, $params);
    }

    protected function loadStats(): void
    {
        $team = Filament::getTenant();

        $this->stats = [
            'total_auxiliares' => DB::table('auxiliares')->where('team_id', $team->id)->count(),
            'total_saldos' => DB::table('saldos_reportes')->where('team_id', $team->id)->count(),
            'ejercicios' => DB::table('auxiliares')
                ->where('team_id', $team->id)
                ->distinct('a_ejercicio')
                ->count('a_ejercicio'),
            'periodos' => DB::table('auxiliares')
                ->where('team_id', $team->id)
                ->where('a_ejercicio', $team->ejercicio)
                ->distinct('a_periodo')
                ->count('a_periodo'),
            'ultima_actualizacion' => DB::table('saldos_reportes')
                ->where('team_id', $team->id)
                ->max('updated_at'),
        ];
    }

    public function limpiarCache(): void
    {
        $team = Filament::getTenant();

        SaldosCache::invalidate($team->id);

        // Intentar limpiar tags solo si el driver lo soporta
        try {
            cache()->tags(['saldos'])->flush();
        } catch (\BadMethodCallException $e) {
            // El driver actual no soporta tags, ignorar
        }

        Notification::make()
            ->success()
            ->title('Cache limpiado')
            ->body('Se ha limpiado el cache de saldos correctamente.')
            ->send();
    }

    protected function recalcularJerarquiaCuentas(int $teamId): void
    {
        // Obtener todas las cuentas que acumulan (cuentas padre)
        // Estas son cuentas de nivel superior que suman sus hijas
        $cuentasPadre = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('acumula', '!=', 'N')
            ->whereRaw('LENGTH(codigo) - LENGTH(REPLACE(codigo, ".", "")) < 2') // Solo niveles 1 y 2
            ->orderBy('codigo')
            ->get();

        foreach ($cuentasPadre as $padre) {
            // Obtener todas las cuentas hijas que comienzan con el código del padre
            $saldoHijas = DB::table('saldos_reportes')
                ->where('team_id', $teamId)
                ->where('codigo', 'LIKE', $padre->codigo . '.%')
                ->where('nivel', '>', substr_count($padre->codigo, '.') + 1)
                ->selectRaw('
                    COALESCE(SUM(anterior), 0) as total_anterior,
                    COALESCE(SUM(cargos), 0) as total_cargos,
                    COALESCE(SUM(abonos), 0) as total_abonos,
                    COALESCE(SUM(final), 0) as total_final
                ')
                ->first();

            if ($saldoHijas) {
                // Actualizar o insertar el saldo de la cuenta padre
                DB::table('saldos_reportes')->updateOrInsert(
                    [
                        'team_id' => $teamId,
                        'codigo' => $padre->codigo,
                    ],
                    [
                        'cuenta' => $padre->nombre,
                        'acumula' => $padre->acumula,
                        'naturaleza' => $padre->naturaleza,
                        'nivel' => substr_count($padre->codigo, '.') + 1,
                        'anterior' => $saldoHijas->total_anterior,
                        'cargos' => $saldoHijas->total_cargos,
                        'abonos' => $saldoHijas->total_abonos,
                        'final' => $saldoHijas->total_final,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    protected function detectarCuentasSinCatalogo(int $teamId): array
    {
        return DB::table('saldos_reportes as sr')
            ->leftJoin('cat_cuentas as cc', function($join) {
                $join->on('cc.team_id', '=', 'sr.team_id')
                     ->on('cc.codigo', '=', 'sr.codigo');
            })
            ->whereNull('cc.id')
            ->where('sr.team_id', $teamId)
            ->select('sr.codigo', 'sr.cuenta', 'sr.final')
            ->get()
            ->toArray();
    }

    protected function detectarCuentasSinMovimientos(int $teamId, ?int $ejercicio, ?int $periodo): array
    {
        $query = DB::table('saldos_reportes as sr')
            ->leftJoin('auxiliares as a', function($join) use ($ejercicio, $periodo) {
                $join->on('a.team_id', '=', 'sr.team_id')
                     ->on('a.codigo', '=', 'sr.codigo');
                if ($ejercicio) {
                    $join->where('a.a_ejercicio', '=', $ejercicio);
                }
                if ($periodo) {
                    $join->where('a.a_periodo', '=', $periodo);
                }
            })
            ->whereNull('a.id')
            ->where('sr.final', '!=', 0)
            ->where('sr.team_id', $teamId)
            ->select('sr.codigo', 'sr.cuenta', 'sr.final')
            ->limit(50) // Limitar a 50 para no sobrecargar el informe
            ->get()
            ->toArray();

        return $query;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
