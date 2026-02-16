<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
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
        $team = Filament::getTenant();

        // Detectar cuentas con el mismo código pero diferentes nombres
        $query = "
            SELECT
                codigo,
                GROUP_CONCAT(id ORDER BY id) as ids,
                GROUP_CONCAT(nombre ORDER BY id SEPARATOR ' | ') as nombres,
                COUNT(*) as cantidad
            FROM cat_cuentas
            WHERE team_id = ?
            GROUP BY codigo
            HAVING COUNT(*) > 1
            ORDER BY codigo
        ";

        $duplicadas = DB::select($query, [$team->id]);

        $this->duplicadas = [];

        foreach ($duplicadas as $dup) {
            $ids = explode(',', $dup->ids);
            $nombres = explode(' | ', $dup->nombres);

            // Obtener detalles de cada cuenta duplicada
            $cuentas = DB::table('cat_cuentas')
                ->whereIn('id', $ids)
                ->where('team_id', $team->id)
                ->orderBy('id')
                ->get();

            $detalles = [];
            foreach ($cuentas as $cuenta) {
                // Contar auxiliares que usan esta cuenta específica
                $auxiliaresCount = DB::table('auxiliares')
                    ->where('team_id', $team->id)
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
                'codigo' => $dup->codigo,
                'cantidad' => (int) $dup->cantidad,
                'cuentas' => $detalles,
            ];
        }
    }

    public function corregirDuplicada(string $codigoOriginal): void
    {
        $this->isProcessing = true;
        $team = Filament::getTenant();

        try {
            DB::beginTransaction();

            // Obtener todas las cuentas con este código
            $cuentas = DB::table('cat_cuentas')
                ->where('team_id', $team->id)
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
                $nuevoCodigo = $this->obtenerSiguienteCodigoDisponible($team->id, $prefijo);

                if (!$nuevoCodigo) {
                    throw new \Exception("No se pudo generar un código disponible con el prefijo {$prefijo}");
                }

                // Contar auxiliares que se van a actualizar
                $auxiliaresAfectados = DB::table('auxiliares')
                    ->where('team_id', $team->id)
                    ->where('codigo', $codigoOriginal)
                    ->where('cuenta', $cuentaSecundaria->nombre)
                    ->count();

                // Actualizar auxiliares que coinciden con código Y nombre
                DB::table('auxiliares')
                    ->where('team_id', $team->id)
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
                    ->where('team_id', $team->id)
                    ->where('codigo', $codigoOriginal)
                    ->where('cuenta', $cuentaSecundaria->nombre)
                    ->update([
                        'codigo' => $nuevoCodigo,
                        'updated_at' => now(),
                    ]);

                // Actualizar saldoscuentas si existe
                DB::table('saldoscuentas')
                    ->where('team_id', $team->id)
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
                    'team_id' => $team->id,
                    'codigo_original' => $codigoOriginal,
                    'codigo_nuevo' => $nuevoCodigo,
                    'cuenta_nombre' => $cuentaSecundaria->nombre,
                    'auxiliares_actualizados' => $auxiliaresAfectados,
                ]);
            }

            DB::commit();

            // Guardar correcciones para mostrar en UI
            $this->correccionesRealizadas = array_merge($this->correccionesRealizadas, $correcciones);

            // Recargar lista de duplicadas
            $this->detectarDuplicadas();

            $mensaje = "Corrección completada:\n";
            $mensaje .= "• Código original: {$codigoOriginal}\n";
            $mensaje .= "• Cuenta principal: {$cuentaPrincipal->nombre}\n";
            $mensaje .= "• Cuentas reasignadas: " . count($correcciones) . "\n";
            $totalAuxiliares = array_sum(array_column($correcciones, 'auxiliares_actualizados'));
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

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al corregir cuenta duplicada', [
                'team_id' => $team->id,
                'codigo' => $codigoOriginal,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error en la corrección')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->persistent()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
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

        foreach ($this->duplicadas as $duplicada) {
            $this->corregirDuplicada($duplicada['codigo']);
        }

        $this->isProcessing = false;

        Notification::make()
            ->success()
            ->title('Corrección Masiva Completada')
            ->body('Se han corregido todas las cuentas duplicadas detectadas.')
            ->send();
    }
}
