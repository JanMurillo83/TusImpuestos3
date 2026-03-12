<?php

namespace App\Filament\Pages;

use App\Models\ReporteResumenEjecutivo;
use App\Services\DashboardIndicadoresService;
use App\Services\ResumenEjecutivoIAService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ResumenEjecutivoPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Resumen Ejecutivo';
    protected static ?string $navigationGroup = 'Reportes';
    protected static string $view = 'filament.pages.resumen-ejecutivo';
    protected ?string $maxContentWidth = 'full';

    public ?array $formData = [];
    public ?string $reporte = null;
    public ?array $datos = null;
    public ?array $secciones = null;
    public ?ReporteResumenEjecutivo $registro = null;
    public ?string $pdfUrl = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $this->form->fill([
            'periodo' => $tenant?->periodo ?? (int) now()->format('m'),
            'ejercicio' => $tenant?->ejercicio ?? (int) now()->format('Y'),
            'forzar' => false,
        ]);

        $this->cargarReporteExistente();
    }

    protected function getFormStatePath(): string
    {
        return 'formData';
    }

    public function getTitle(): string
    {
        return 'Resumen Ejecutivo Financiero-Comercial';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('periodo')
                        ->label('Periodo')
                        ->options($this->periodos())
                        ->required(),
                    Forms\Components\TextInput::make('ejercicio')
                        ->label('Ejercicio')
                        ->numeric()
                        ->required(),
                    Forms\Components\Toggle::make('forzar')
                        ->label('Regenerar reporte')
                        ->helperText('Si existe un reporte para el periodo seleccionado, se vuelve a generar.'),
                ])
                ->columns(3),
        ];
    }

    public function generarResumen(DashboardIndicadoresService $indicadoresService, ResumenEjecutivoIAService $iaService): void
    {
        $data = $this->form->getState();
        $periodo = (int) ($data['periodo'] ?? 0);
        $ejercicio = (int) ($data['ejercicio'] ?? 0);
        $forzar = (bool) ($data['forzar'] ?? false);

        if ($periodo < 1 || $periodo > 12 || $ejercicio < 2000) {
            Notification::make()->title('Periodo inválido.')->danger()->send();
            return;
        }

        $tenant = Filament::getTenant();
        if (! $tenant) {
            Notification::make()->title('No se encontró el tenant activo.')->danger()->send();
            return;
        }

        // Nota: tenant_id corresponde al team_id en los módulos actuales.
        $clavePeriodo = sprintf('%04d-%02d', $ejercicio, $periodo);
        // Evita regenerar si ya existe reporte para el periodo.
        $existente = ReporteResumenEjecutivo::where('tenant_id', $tenant->id)
            ->where('periodo', $clavePeriodo)
            ->latest()
            ->first();

        if ($existente && ! $forzar) {
            $this->aplicarReporte($existente);
            Notification::make()->title('Se cargó el reporte existente.')->success()->send();
            return;
        }

        try {
            // Periodo mensual según ejercicio y periodo del tenant.
            $inicio = Carbon::create($ejercicio, $periodo, 1)->startOfMonth();
            $fin = Carbon::create($ejercicio, $periodo, 1)->endOfMonth();

            $financieros = $indicadoresService->obtenerIndicadoresFinancieros($tenant->id, $periodo, $ejercicio);
            $comerciales = $indicadoresService->obtenerIndicadoresComerciales($tenant->id, $inicio, $fin);

            $indicadores = array_merge($financieros, [
                'cotizaciones' => $comerciales['numero_cotizaciones'],
                'monto_cotizado' => $comerciales['monto_cotizado'],
                'facturacion' => $comerciales['facturacion'],
                'conversion_comercial' => $comerciales['conversion_comercial'],
                'conversion_ponderada' => $comerciales['conversion_ponderada'],
                'ciclo_comercial_dias' => $comerciales['ciclo_promedio'],
                'ventas_directas' => $comerciales['ventas_directas'],
            ]);

            // Generación con IA a partir del JSON de indicadores.
            $reporte = $iaService->generarReporte($indicadores);

            $registro = ReporteResumenEjecutivo::updateOrCreate(
                ['tenant_id' => $tenant->id, 'periodo' => $clavePeriodo],
                ['datos' => $indicadores, 'reporte' => $reporte]
            );

            $this->aplicarReporte($registro);

            Notification::make()->title('Reporte generado correctamente.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No fue posible generar el reporte.')->body($e->getMessage())->danger()->send();
        }
    }

    private function cargarReporteExistente(): void
    {
        $data = $this->form->getState();
        $periodo = (int) ($data['periodo'] ?? 0);
        $ejercicio = (int) ($data['ejercicio'] ?? 0);
        if ($periodo < 1 || $periodo > 12 || $ejercicio < 2000) {
            return;
        }

        $tenant = Filament::getTenant();
        if (! $tenant) {
            return;
        }

        $clavePeriodo = sprintf('%04d-%02d', $ejercicio, $periodo);
        $registro = ReporteResumenEjecutivo::where('tenant_id', $tenant->id)
            ->where('periodo', $clavePeriodo)
            ->latest()
            ->first();

        if ($registro) {
            $this->aplicarReporte($registro);
        }
    }

    public function updatedFormData($value, $key): void
    {
        if (in_array($key, ['periodo', 'ejercicio'], true)) {
            $this->cargarReporteExistente();
        }
    }

    private function aplicarReporte(ReporteResumenEjecutivo $registro): void
    {
        $this->registro = $registro;
        $this->reporte = $registro->reporte;
        $this->datos = $registro->datos;
        $this->secciones = $this->parsearSecciones($registro->reporte);
        $tenant = Filament::getTenant();
        $this->pdfUrl = $tenant
            ? url("/{$tenant->id}/reportes/resumen-ejecutivo/pdf?periodo={$registro->periodo}")
            : null;
    }

    private function parsearSecciones(string $texto): ?array
    {
        $heads = [
            'Resumen Ejecutivo',
            'Indicadores Clave',
            'Análisis Financiero',
            'Análisis Comercial',
            'Hallazgos Estratégicos',
            'Recomendaciones Ejecutivas',
            'Conclusión General',
        ];

        $result = array_fill_keys($heads, '');
        $normalized = str_replace("\r\n", "\n", $texto);

        $current = null;
        foreach (explode("\n", $normalized) as $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '') {
                continue;
            }
            $lineNorm = $this->normalizarEncabezado($lineTrim);
            foreach ($heads as $head) {
                if (strcasecmp($lineNorm, $head) === 0) {
                    $current = $head;
                    continue 2;
                }
            }
            if ($current) {
                $result[$current] .= ($result[$current] ? "\n" : '') . $lineTrim;
            }
        }

        $tieneContenido = false;
        foreach ($result as $contenido) {
            if (trim($contenido) !== '') {
                $tieneContenido = true;
                break;
            }
        }

        return $tieneContenido ? $result : null;
    }

    private function normalizarEncabezado(string $linea): string
    {
        $linea = preg_replace('/^[#\\-*\\s]+/', '', $linea) ?? $linea;
        $linea = rtrim($linea, ':');
        return trim($linea);
    }

    private function periodos(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }
}
