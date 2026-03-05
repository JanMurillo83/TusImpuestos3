<?php

use App\Http\Controllers\ReportesController;
use App\Models\SaldosReportes;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class BalanzaExport implements FromView
{
    use Exportable;

    protected $empresa;
    protected $periodo;
    protected $ejercicio;

    public function __construct(int $empresa, int $periodo, int $ejercicio)
    {
        $this->empresa = $empresa;
        $this->periodo = $periodo;
        $this->ejercicio = $ejercicio;
    }

    public function view(): View
    {
        $team = Team::find($this->empresa);

        app(ReportesController::class)->ContabilizaReporte($this->ejercicio, $this->periodo, $this->empresa);

        $results = SaldosReportes::query()
            ->where('team_id', $this->empresa)
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

        $balanza = [];
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

            $balanza[] = $item;

            if ((int)$row->nivel === 1) {
                $totales['saldo_ant_deudor'] += $saldoAntDeudor;
                $totales['saldo_ant_acreedor'] += $saldoAntAcreedor;
                $totales['cargos'] += $c;
                $totales['abonos'] += $a;
                $totales['saldo_deudor'] += $saldo_deudor;
                $totales['saldo_acreedor'] += $saldo_acreedor;
            }
        }

        return view('BalanzaNewExport', [
            'balanza' => $balanza,
            'totales' => $totales,
            'ejercicio' => $this->ejercicio,
            'periodo' => $this->periodo,
            'empresa' => $team,
            'fecha_emision' => now()->format('d/m/Y'),
        ]);
    }
}
