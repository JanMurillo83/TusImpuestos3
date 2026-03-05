<?php

namespace App\Filament\Pages;

use App\Exports\BalanzaExport;
use App\Http\Controllers\ReportesController;
use App\Models\SaldosReportes;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

class BalanzaComprobacion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $navigationLabel = 'Balanza de Comprobación';
    protected static ?string $title = 'Balanza de Comprobación';
    protected static string $view = 'filament.pages.balanza-comprobacion';
    protected static ?int $navigationSort = 4;

    public ?int $ejercicio = null;
    public ?int $periodo = null;
    public ?array $data = [];
    public array $balanza = [];
    public array $totales = [];

    public function mount(): void
    {
        $team = Filament::getTenant();

        $this->ejercicio = $team->ejercicio ?? date('Y');
        $this->periodo = $team->periodo ?? 1;

        $this->form->fill([
            'ejercicio' => $this->ejercicio,
            'periodo' => $this->periodo,
        ]);

        $this->cargarBalanza();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('PDF')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportPdf'),
            Action::make('export_excel')
                ->label('Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportExcel'),
        ];
    }

    public function exportPdf()
    {
        $this->cargarBalanza();
        $team = Filament::getTenant();

        $data = [
            'balanza' => $this->balanza,
            'totales' => $this->totales,
            'ejercicio' => $this->ejercicio,
            'periodo' => $this->periodo,
            'empresa' => $team,
            'fecha_emision' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('BalanzaNewExport', $data)
            ->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "Balanza_{$this->ejercicio}_{$this->periodo}.pdf");
    }

    public function exportExcel()
    {
        $this->cargarBalanza();
        $team = Filament::getTenant();

        return (new BalanzaExport($team->id, $this->periodo, $this->ejercicio))
            ->download("Balanza_{$this->ejercicio}_{$this->periodo}.xlsx");
    }

    protected function getFormSchema(): array
    {
        $team = Filament::getTenant();

        // Obtener ejercicios disponibles
        /*$ejercicios = DB::table('auxiliares')
            ->where('team_id', $team->id)
            ->distinct('a_ejercicio')
            ->orderBy('a_ejercicio', 'desc')
            ->pluck('a_ejercicio', 'a_ejercicio')
            ->toArray();*/
        $ejercicios = [2023=>2023,2024=>2024,2025=>2025,2026=>2026,2027=>2027,2028=>2028];

        return [
            Select::make('ejercicio')
                ->label('Ejercicio')
                ->options($ejercicios)
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->cargarBalanza()),

            Select::make('periodo')
                ->label('Periodo')
                ->options([
                    1 => '01 - Enero',
                    2 => '02 - Febrero',
                    3 => '03 - Marzo',
                    4 => '04 - Abril',
                    5 => '05 - Mayo',
                    6 => '06 - Junio',
                    7 => '07 - Julio',
                    8 => '08 - Agosto',
                    9 => '09 - Septiembre',
                    10 => '10 - Octubre',
                    11 => '11 - Noviembre',
                    12 => '12 - Diciembre',
                    13 => '13 - Cierre Anual',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->cargarBalanza()),
        ];
    }

    public function cargarBalanza(): void
    {
        $data = $this->form->getState();
        $this->ejercicio = $data['ejercicio'] ?? $this->ejercicio;
        $this->periodo = $data['periodo'] ?? $this->periodo;

        $team = Filament::getTenant();

        app(ReportesController::class)->ContabilizaReporte($this->ejercicio, $this->periodo, $team->id);

        $results = SaldosReportes::query()
            ->where('team_id', $team->id)
            ->where('ejercicio', $this->ejercicio)
            ->where('periodo', $this->periodo)
            ->where(function ($query) {
                $query->where('anterior', '!=', 0)
                    ->orWhere('cargos', '!=', 0)
                    ->orWhere('abonos', '!=', 0)
                    ->orWhere('final', '!=', 0);
            })
            ->orderBy('codigo')
            ->get();

        // Calcular saldos y preparar datos
        $this->balanza = [];
        $totales = [
            'saldo_ant_deudor' => 0,
            'saldo_ant_acreedor' => 0,
            'cargos' => 0,
            'abonos' => 0,
            'saldo_deudor' => 0,
            'saldo_acreedor' => 0,
        ];

        foreach ($results as $row) {
            $saldoAnterior = (float)$row->anterior;
            $saldoFinal = (float)$row->final;
            $c = (float)$row->cargos;
            $a = (float)$row->abonos;

            if ($row->naturaleza === 'D') {
                $saldoAntDeudor = $saldoAnterior > 0 ? $saldoAnterior : 0;
                $saldoAntAcreedor = $saldoAnterior < 0 ? abs($saldoAnterior) : 0;
                $saldo_deudor = $saldoFinal > 0 ? $saldoFinal : 0;
                $saldo_acreedor = $saldoFinal < 0 ? abs($saldoFinal) : 0;
            } else {
                $saldoAntDeudor = $saldoAnterior < 0 ? abs($saldoAnterior) : 0;
                $saldoAntAcreedor = $saldoAnterior > 0 ? $saldoAnterior : 0;
                $saldo_deudor = $saldoFinal < 0 ? abs($saldoFinal) : 0;
                $saldo_acreedor = $saldoFinal > 0 ? $saldoFinal : 0;
            }

            $item = [
                'codigo' => $row->codigo,
                'cuenta' => $row->cuenta,
                'naturaleza' => $row->naturaleza,
                'nivel' => $row->nivel,
                'saldo_ant_deudor' => $saldoAntDeudor,
                'saldo_ant_acreedor' => $saldoAntAcreedor,
                'saldo_anterior' => $saldoAnterior,
                'cargos' => $c,
                'abonos' => $a,
                'saldo_final' => $saldoFinal,
                'saldo_deudor' => $saldo_deudor,
                'saldo_acreedor' => $saldo_acreedor,
            ];

            $this->balanza[] = $item;

            // Acumular totales (solo nivel 1 para evitar duplicidad)
            if ((int)$row->nivel === 1) {
                $totales['saldo_ant_deudor'] += $saldoAntDeudor;
                $totales['saldo_ant_acreedor'] += $saldoAntAcreedor;
                $totales['cargos'] += $c;
                $totales['abonos'] += $a;
                $totales['saldo_deudor'] += $saldo_deudor;
                $totales['saldo_acreedor'] += $saldo_acreedor;
            }
        }

        $this->totales = $totales;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
