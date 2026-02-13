<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\CatPolizas;
use App\Models\Auxiliares;

class AnalisisPolizasDuplicadas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string $view = 'filament.pages.analisis-polizas-duplicadas';
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $navigationLabel = 'Análisis Duplicados';
    protected static ?string $title = 'Análisis de Pólizas Duplicadas';

    public $duplicados = [];
    public $hayDuplicados = false;

    public static function shouldRegisterNavigation(): bool
    {
        // Temporalmente oculto - implementar después
        return false;
        // return auth()->user()->hasRole(['administrador', 'contador']);
    }

    public function mount(): void
    {
        $this->analizarDuplicados();
    }

    public function analizarDuplicados(): void
    {
        $duplicados = DB::select("
            SELECT
                team_id,
                tipo,
                folio,
                periodo,
                ejercicio,
                COUNT(*) as cantidad
            FROM cat_polizas
            GROUP BY team_id, tipo, folio, periodo, ejercicio
            HAVING cantidad > 1
            ORDER BY team_id, ejercicio, periodo, tipo, folio
        ");

        $this->duplicados = [];

        foreach ($duplicados as $dup) {
            $polizas = CatPolizas::where('team_id', $dup->team_id)
                ->where('tipo', $dup->tipo)
                ->where('folio', $dup->folio)
                ->where('periodo', $dup->periodo)
                ->where('ejercicio', $dup->ejercicio)
                ->orderBy('id')
                ->get();

            $detallePolizas = [];
            foreach ($polizas as $poliza) {
                $partidas = Auxiliares::where('cat_polizas_id', $poliza->id)->count();
                $detallePolizas[] = [
                    'id' => $poliza->id,
                    'fecha' => $poliza->fecha->format('d/m/Y'),
                    'concepto' => $poliza->concepto,
                    'referencia' => $poliza->referencia,
                    'cargos' => $poliza->cargos,
                    'abonos' => $poliza->abonos,
                    'partidas' => $partidas,
                    'uuid' => $poliza->uuid,
                    'creado' => $poliza->created_at->format('d/m/Y H:i:s'),
                ];
            }

            $this->duplicados[] = [
                'team_id' => $dup->team_id,
                'tipo' => $dup->tipo,
                'folio' => $dup->folio,
                'periodo' => $dup->periodo,
                'ejercicio' => $dup->ejercicio,
                'cantidad' => $dup->cantidad,
                'polizas' => $detallePolizas,
            ];
        }

        $this->hayDuplicados = count($this->duplicados) > 0;

        if (!$this->hayDuplicados) {
            Notification::make()
                ->title('No se encontraron duplicados')
                ->success()
                ->send();
        }
    }

    public function exportar(): void
    {
        $filename = storage_path('app/polizas_duplicadas_' . date('Y-m-d_His') . '.json');
        file_put_contents($filename, json_encode($this->duplicados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title('Reporte exportado')
            ->body('El archivo se guardó en: ' . $filename)
            ->success()
            ->send();
    }
}
