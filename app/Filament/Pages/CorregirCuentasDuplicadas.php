<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorregirCuentasDuplicadas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Herramientas';
    protected static ?string $navigationLabel = 'Corregir Cuentas Duplicadas';
    protected static ?string $title = 'Detectar y Corregir Cuentas Duplicadas';
    protected static string $view = 'filament.pages.corregir-cuentas-duplicadas';
    protected static ?int $navigationSort = 15;

    public array $duplicadas = [];
    public array $correccionesRealizadas = [];
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->detectarDuplicadas();
    }

    public function detectarDuplicadas(): void
    {
        // Detectar cuentas con el mismo código (todas las empresas)
        $query = "
            SELECT
                team_id,
                codigo,
                GROUP_CONCAT(id ORDER BY id) as ids,
                GROUP_CONCAT(nombre ORDER BY id SEPARATOR ' | ') as nombres,
                COUNT(*) as cantidad
            FROM cat_cuentas
            GROUP BY team_id, codigo
            HAVING COUNT(*) > 1
            ORDER BY team_id, codigo
        ";

        $duplicadasCodigo = DB::select($query);

        $teams = DB::table('teams')->pluck('name', 'id');

        $this->duplicadas = [];

        foreach ($duplicadasCodigo as $dup) {
            $ids = explode(',', $dup->ids);
            $nombres = explode(' | ', $dup->nombres);

            // Obtener detalles de cada cuenta duplicada
            $cuentas = DB::table('cat_cuentas')
                ->whereIn('id', $ids)
                ->where('team_id', $dup->team_id)
                ->orderBy('id')
                ->get();

            $detalles = [];
            foreach ($cuentas as $cuenta) {
                // Contar auxiliares que usan esta cuenta específica
                $auxiliaresCount = DB::table('auxiliares')
                    ->where('team_id', $dup->team_id)
                    ->where('codigo', $cuenta->codigo)
                    ->where('cuenta', $cuenta->nombre)
                    ->count();

                $detalles[] = [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'naturaleza' => $cuenta->naturaleza,
                    'acumula' => $cuenta->acumula,
                    'auxiliares_count' => $auxiliaresCount,
                    'es_primera' => $cuenta->id == $ids[0],
                ];
            }

            $this->duplicadas[] = [
                'tipo' => 'codigo',
                'team_id' => $dup->team_id,
                'team_name' => $teams[$dup->team_id] ?? ('Empresa #' . $dup->team_id),
                'codigo' => $dup->codigo,
                'cantidad' => (int) $dup->cantidad,
                'cuentas' => $detalles,
            ];
        }

        // Detectar cuentas con la misma combinación nombre + acumula (todas las empresas)
        $queryNombreAcumula = "
            SELECT
                team_id,
                nombre,
                acumula,
                GROUP_CONCAT(id ORDER BY id) as ids,
                GROUP_CONCAT(codigo ORDER BY id SEPARATOR ' | ') as codigos,
                COUNT(*) as cantidad
            FROM cat_cuentas
            WHERE nombre IS NOT NULL AND nombre <> ''
              AND acumula IS NOT NULL AND acumula <> ''
            GROUP BY team_id, nombre, acumula
            HAVING COUNT(*) > 1
            ORDER BY team_id, nombre, acumula
        ";

        $duplicadasNombreAcumula = DB::select($queryNombreAcumula);

        foreach ($duplicadasNombreAcumula as $dup) {
            $ids = explode(',', $dup->ids);

            // Obtener detalles de cada cuenta duplicada
            $cuentas = DB::table('cat_cuentas')
                ->whereIn('id', $ids)
                ->where('team_id', $dup->team_id)
                ->orderBy('id')
                ->get();

            $detalles = [];
            foreach ($cuentas as $cuenta) {
                $auxiliaresCount = DB::table('auxiliares')
                    ->where('team_id', $dup->team_id)
                    ->where('codigo', $cuenta->codigo)
                    ->where('cuenta', $cuenta->nombre)
                    ->count();

                $detalles[] = [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'naturaleza' => $cuenta->naturaleza,
                    'acumula' => $cuenta->acumula,
                    'auxiliares_count' => $auxiliaresCount,
                    'es_primera' => $cuenta->id == $ids[0],
                ];
            }

            $this->duplicadas[] = [
                'tipo' => 'nombre_acumula',
                'team_id' => $dup->team_id,
                'team_name' => $teams[$dup->team_id] ?? ('Empresa #' . $dup->team_id),
                'nombre' => $dup->nombre,
                'acumula' => $dup->acumula,
                'cantidad' => (int) $dup->cantidad,
                'cuentas' => $detalles,
            ];
        }
    }

    public function corregirDuplicada(string $codigoOriginal, int $teamId, bool $silent = false): array
    {
        if (! $silent) {
            $this->isProcessing = true;
        }

        try {
            DB::beginTransaction();

            // Obtener todas las cuentas con este código
            $cuentas = DB::table('cat_cuentas')
                ->where('team_id', $teamId)
                ->where('codigo', $codigoOriginal)
                ->orderBy('id')
                ->get();

            if ($cuentas->count() < 2) {
                throw new \Exception("No se encontraron cuentas duplicadas para el código {$codigoOriginal}");
            }

            // La primera cuenta se queda (más antigua)
            $cuentaPrincipal = $cuentas->first();
            $cuentasSecundarias = $cuentas->slice(1);

            $correcciones = [];

            foreach ($cuentasSecundarias as $cuentaSecundaria) {
                // Buscar el siguiente código disponible con el prefijo
                $prefijo = substr($codigoOriginal, 0, 5); // Ej: "10501" de "10501065"
                $nuevoCodigo = $this->obtenerSiguienteCodigoDisponible($teamId, $prefijo);

                if (!$nuevoCodigo) {
                    throw new \Exception("No se pudo generar un código disponible con el prefijo {$prefijo}");
                }

                // Contar auxiliares que se van a actualizar
                $auxiliaresAfectados = DB::table('auxiliares')
                    ->where('team_id', $teamId)
                    ->where('codigo', $codigoOriginal)
                    ->where('cuenta', $cuentaSecundaria->nombre)
                    ->count();

                // Actualizar auxiliares que coinciden con código Y nombre
                DB::table('auxiliares')
                    ->where('team_id', $teamId)
                    ->where('codigo', $codigoOriginal)
                    ->where('cuenta', $cuentaSecundaria->nombre)
                    ->update([
                        'codigo' => $nuevoCodigo,
                        'updated_at' => now(),
                    ]);

                // Actualizar el código en cat_cuentas
                DB::table('cat_cuentas')
                    ->where('id', $cuentaSecundaria->id)
                    ->update([
                        'codigo' => $nuevoCodigo,
                        'updated_at' => now(),
                    ]);

                // Actualizar saldos_reportes si existe
                DB::table('saldos_reportes')
                    ->where('team_id', $teamId)
                    ->where('codigo', $codigoOriginal)
                    ->where('cuenta', $cuentaSecundaria->nombre)
                    ->update([
                        'codigo' => $nuevoCodigo,
                        'updated_at' => now(),
                    ]);

                // Actualizar saldoscuentas si existe
                DB::table('saldoscuentas')
                    ->where('team_id', $teamId)
                    ->where('codigo', $codigoOriginal)
                    ->update([
                        'codigo' => $nuevoCodigo,
                        'updated_at' => now(),
                    ]);

                $correcciones[] = [
                    'codigo_original' => $codigoOriginal,
                    'codigo_nuevo' => $nuevoCodigo,
                    'nombre' => $cuentaSecundaria->nombre,
                    'cat_cuentas_id' => $cuentaSecundaria->id,
                    'auxiliares_actualizados' => $auxiliaresAfectados,
                ];

                Log::info('Cuenta duplicada corregida', [
                    'team_id' => $teamId,
                    'codigo_original' => $codigoOriginal,
                    'codigo_nuevo' => $nuevoCodigo,
                    'cuenta_nombre' => $cuentaSecundaria->nombre,
                    'auxiliares_actualizados' => $auxiliaresAfectados,
                ]);
            }

            DB::commit();

            $totalAuxiliares = array_sum(array_column($correcciones, 'auxiliares_actualizados'));

            // Guardar correcciones para mostrar en UI
            $this->correccionesRealizadas = array_merge($this->correccionesRealizadas, $correcciones);

            if (! $silent) {
                // Recargar lista de duplicadas
                $this->detectarDuplicadas();

                $mensaje = "Corrección completada:\n";
                $mensaje .= "• Código original: {$codigoOriginal}\n";
                $mensaje .= "• Cuenta principal: {$cuentaPrincipal->nombre}\n";
                $mensaje .= "• Cuentas reasignadas: " . count($correcciones) . "\n";
                $mensaje .= "• Total auxiliares actualizados: {$totalAuxiliares}\n";
                $mensaje .= "\nNuevos códigos asignados:\n";
                foreach ($correcciones as $corr) {
                    $mensaje .= "  - {$corr['codigo_nuevo']}: {$corr['nombre']}\n";
                }

                Notification::make()
                    ->success()
                    ->title('Corrección Completada')
                    ->body($mensaje)
                    ->persistent()
                    ->send();
            }

            return [
                'ok' => true,
                'tipo' => 'codigo',
                'team_id' => $teamId,
                'codigo' => $codigoOriginal,
                'cuentas_reasignadas' => count($correcciones),
                'auxiliares_actualizados' => $totalAuxiliares,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al corregir cuenta duplicada', [
                'team_id' => $teamId,
                'codigo' => $codigoOriginal,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (! $silent) {
                Notification::make()
                    ->danger()
                    ->title('Error en la corrección')
                    ->body('Ocurrió un error: ' . $e->getMessage())
                    ->persistent()
                    ->send();
            }

            return [
                'ok' => false,
                'tipo' => 'codigo',
                'team_id' => $teamId,
                'codigo' => $codigoOriginal,
                'error' => $e->getMessage(),
            ];
        } finally {
            if (! $silent) {
                $this->isProcessing = false;
            }
        }
    }

    public function corregirDuplicadaNombreAcumula(string $nombre, string $acumula, int $teamId, bool $silent = false): array
    {
        if (! $silent) {
            $this->isProcessing = true;
        }

        try {
            DB::beginTransaction();

            $cuentas = DB::table('cat_cuentas')
                ->where('team_id', $teamId)
                ->where('nombre', $nombre)
                ->where('acumula', $acumula)
                ->orderBy('id')
                ->get();

            if ($cuentas->count() < 2) {
                throw new \Exception("No se encontraron cuentas duplicadas para {$nombre} / {$acumula}");
            }

            $cuentaPrincipal = $cuentas->first();
            $cuentasSecundarias = $cuentas->slice(1);

            $correcciones = [];

            foreach ($cuentasSecundarias as $cuentaSecundaria) {
                $auxiliaresAfectados = DB::table('auxiliares')
                    ->where('team_id', $teamId)
                    ->where('codigo', $cuentaSecundaria->codigo)
                    ->count();

                DB::table('auxiliares')
                    ->where('team_id', $teamId)
                    ->where('codigo', $cuentaSecundaria->codigo)
                    ->update([
                        'codigo' => $cuentaPrincipal->codigo,
                        'updated_at' => now(),
                    ]);

                DB::table('saldos_reportes')
                    ->where('team_id', $teamId)
                    ->where('codigo', $cuentaSecundaria->codigo)
                    ->update([
                        'codigo' => $cuentaPrincipal->codigo,
                        'updated_at' => now(),
                    ]);

                DB::table('saldoscuentas')
                    ->where('team_id', $teamId)
                    ->where('codigo', $cuentaSecundaria->codigo)
                    ->update([
                        'codigo' => $cuentaPrincipal->codigo,
                        'updated_at' => now(),
                    ]);

                DB::table('cat_cuentas_team')
                    ->where('cat_cuentas_id', $cuentaSecundaria->id)
                    ->delete();

                DB::table('cat_cuentas')
                    ->where('id', $cuentaSecundaria->id)
                    ->delete();

                $correcciones[] = [
                    'codigo_original' => $cuentaSecundaria->codigo,
                    'codigo_nuevo' => $cuentaPrincipal->codigo,
                    'nombre' => $cuentaSecundaria->nombre,
                    'cat_cuentas_id' => $cuentaSecundaria->id,
                    'auxiliares_actualizados' => $auxiliaresAfectados,
                ];

                Log::info('Cuenta duplicada por nombre/acumula corregida', [
                    'team_id' => $teamId,
                    'codigo_principal' => $cuentaPrincipal->codigo,
                    'codigo_eliminado' => $cuentaSecundaria->codigo,
                    'cuenta_nombre' => $cuentaSecundaria->nombre,
                    'acumula' => $cuentaSecundaria->acumula,
                    'auxiliares_actualizados' => $auxiliaresAfectados,
                ]);
            }

            DB::commit();

            $totalAuxiliares = array_sum(array_column($correcciones, 'auxiliares_actualizados'));

            $this->correccionesRealizadas = array_merge($this->correccionesRealizadas, $correcciones);

            if (! $silent) {
                $this->detectarDuplicadas();

                $mensaje = "Corrección completada:\n";
                $mensaje .= "• Cuenta principal: {$cuentaPrincipal->codigo} - {$cuentaPrincipal->nombre}\n";
                $mensaje .= "• Cuentas eliminadas: " . count($correcciones) . "\n";
                $mensaje .= "• Total auxiliares actualizados: {$totalAuxiliares}\n";
                $mensaje .= "\nCódigos consolidados:\n";
                foreach ($correcciones as $corr) {
                    $mensaje .= "  - {$corr['codigo_original']} -> {$corr['codigo_nuevo']}\n";
                }

                Notification::make()
                    ->success()
                    ->title('Corrección Completada')
                    ->body($mensaje)
                    ->persistent()
                    ->send();
            }

            return [
                'ok' => true,
                'tipo' => 'nombre_acumula',
                'team_id' => $teamId,
                'nombre' => $nombre,
                'acumula' => $acumula,
                'cuentas_eliminadas' => count($correcciones),
                'auxiliares_actualizados' => $totalAuxiliares,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al corregir cuenta duplicada por nombre/acumula', [
                'team_id' => $teamId,
                'nombre' => $nombre,
                'acumula' => $acumula,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (! $silent) {
                Notification::make()
                    ->danger()
                    ->title('Error en la corrección')
                    ->body('Ocurrió un error: ' . $e->getMessage())
                    ->persistent()
                    ->send();
            }

            return [
                'ok' => false,
                'tipo' => 'nombre_acumula',
                'team_id' => $teamId,
                'nombre' => $nombre,
                'acumula' => $acumula,
                'error' => $e->getMessage(),
            ];
        } finally {
            if (! $silent) {
                $this->isProcessing = false;
            }
        }
    }

    public function corregirDuplicadaNombreAcumulaIndex(int $index): void
    {
        if (! isset($this->duplicadas[$index])) {
            Notification::make()
                ->danger()
                ->title('Error en la corrección')
                ->body('No se encontró el registro seleccionado.')
                ->persistent()
                ->send();
            return;
        }

        $duplicada = $this->duplicadas[$index];
        if (($duplicada['tipo'] ?? 'codigo') !== 'nombre_acumula') {
            Notification::make()
                ->danger()
                ->title('Error en la corrección')
                ->body('El registro seleccionado no es una duplicidad por nombre + acumula.')
                ->persistent()
                ->send();
            return;
        }

        $this->corregirDuplicadaNombreAcumula($duplicada['nombre'], $duplicada['acumula'], $duplicada['team_id']);
    }

    protected function obtenerSiguienteCodigoDisponible(int $teamId, string $prefijo): ?string
    {
        // Buscar el código más alto con este prefijo
        $ultimoCodigo = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderByRaw('CAST(SUBSTRING(codigo, ' . (strlen($prefijo) + 1) . ') AS UNSIGNED) DESC')
            ->value('codigo');

        if ($ultimoCodigo) {
            // Extraer el número del final
            $sufijo = substr($ultimoCodigo, strlen($prefijo));
            $numero = (int) $sufijo;
            $nuevoNumero = $numero + 1;
        } else {
            // Si no existe ninguno con este prefijo, empezar desde 001
            $nuevoNumero = 1;
        }

        // Formatear con ceros a la izquierda (3 dígitos)
        $nuevoSufijo = str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);
        $nuevoCodigo = $prefijo . $nuevoSufijo;

        // Verificar que no exista
        $existe = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('codigo', $nuevoCodigo)
            ->exists();

        if ($existe) {
            // Si existe, intentar con el siguiente
            return $this->obtenerSiguienteCodigoDisponible($teamId, $prefijo);
        }

        return $nuevoCodigo;
    }

    public function corregirTodas(): void
    {
        $this->isProcessing = true;

        $resumen = [
            'codigo' => ['grupos' => 0, 'cuentas' => 0, 'auxiliares' => 0, 'errores' => 0],
            'nombre_acumula' => ['grupos' => 0, 'cuentas' => 0, 'auxiliares' => 0, 'errores' => 0],
        ];
        $errores = [];

        foreach ($this->duplicadas as $duplicada) {
            if (($duplicada['tipo'] ?? 'codigo') === 'nombre_acumula') {
                $resumen['nombre_acumula']['grupos']++;
                $resultado = $this->corregirDuplicadaNombreAcumula($duplicada['nombre'], $duplicada['acumula'], $duplicada['team_id'], true);
                if (! $resultado['ok']) {
                    $resumen['nombre_acumula']['errores']++;
                    $errores[] = $resultado['error'] ?? 'Error desconocido';
                } else {
                    $resumen['nombre_acumula']['cuentas'] += $resultado['cuentas_eliminadas'] ?? 0;
                    $resumen['nombre_acumula']['auxiliares'] += $resultado['auxiliares_actualizados'] ?? 0;
                }
                continue;
            }
            $resumen['codigo']['grupos']++;
            $resultado = $this->corregirDuplicada($duplicada['codigo'], $duplicada['team_id'], true);
            if (! $resultado['ok']) {
                $resumen['codigo']['errores']++;
                $errores[] = $resultado['error'] ?? 'Error desconocido';
            } else {
                $resumen['codigo']['cuentas'] += $resultado['cuentas_reasignadas'] ?? 0;
                $resumen['codigo']['auxiliares'] += $resultado['auxiliares_actualizados'] ?? 0;
            }
        }

        $this->isProcessing = false;

        $this->detectarDuplicadas();

        $mensaje = "Resumen de la corrección general:\n";
        $mensaje .= "• Por código: {$resumen['codigo']['grupos']} grupos, {$resumen['codigo']['cuentas']} cuentas reasignadas, {$resumen['codigo']['auxiliares']} auxiliares actualizados, {$resumen['codigo']['errores']} errores.\n";
        $mensaje .= "• Por nombre + acumula: {$resumen['nombre_acumula']['grupos']} grupos, {$resumen['nombre_acumula']['cuentas']} cuentas eliminadas, {$resumen['nombre_acumula']['auxiliares']} auxiliares actualizados, {$resumen['nombre_acumula']['errores']} errores.\n";
        if (! empty($errores)) {
            $mensaje .= "\nErrores (primeros 3):\n";
            foreach (array_slice($errores, 0, 3) as $err) {
                $mensaje .= "  - {$err}\n";
            }
        }

        Notification::make()
            ->success()
            ->title('Corrección Masiva Completada')
            ->body($mensaje)
            ->send();
    }

    public function consolidarTodasNombreAcumula(): void
    {
        $this->isProcessing = true;

        $resumen = ['grupos' => 0, 'cuentas' => 0, 'auxiliares' => 0, 'errores' => 0];
        $errores = [];

        foreach ($this->duplicadas as $duplicada) {
            if (($duplicada['tipo'] ?? 'codigo') !== 'nombre_acumula') {
                continue;
            }

            $resumen['grupos']++;
            $resultado = $this->corregirDuplicadaNombreAcumula($duplicada['nombre'], $duplicada['acumula'], $duplicada['team_id'], true);
            if (! $resultado['ok']) {
                $resumen['errores']++;
                $errores[] = $resultado['error'] ?? 'Error desconocido';
            } else {
                $resumen['cuentas'] += $resultado['cuentas_eliminadas'] ?? 0;
                $resumen['auxiliares'] += $resultado['auxiliares_actualizados'] ?? 0;
            }
        }

        $this->isProcessing = false;
        $this->detectarDuplicadas();

        $mensaje = "Resumen de la consolidación:\n";
        $mensaje .= "• Grupos: {$resumen['grupos']}\n";
        $mensaje .= "• Cuentas eliminadas: {$resumen['cuentas']}\n";
        $mensaje .= "• Auxiliares actualizados: {$resumen['auxiliares']}\n";
        $mensaje .= "• Errores: {$resumen['errores']}\n";
        if (! empty($errores)) {
            $mensaje .= "\nErrores (primeros 3):\n";
            foreach (array_slice($errores, 0, 3) as $err) {
                $mensaje .= "  - {$err}\n";
            }
        }

        Notification::make()
            ->success()
            ->title('Consolidación Masiva Completada')
            ->body($mensaje)
            ->send();
    }
}
