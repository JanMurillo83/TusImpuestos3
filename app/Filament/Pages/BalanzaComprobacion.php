<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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

    protected function getFormSchema(): array
    {
        $team = Filament::getTenant();

        // Obtener ejercicios disponibles
        $ejercicios = DB::table('auxiliares')
            ->where('team_id', $team->id)
            ->distinct('a_ejercicio')
            ->orderBy('a_ejercicio', 'desc')
            ->pluck('a_ejercicio', 'a_ejercicio')
            ->toArray();

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

        // Consulta para obtener la balanza de comprobación
        $query = "
            SELECT
                cc.codigo,
                cc.nombre as cuenta,
                cc.naturaleza,
                CHAR_LENGTH(cc.codigo) - CHAR_LENGTH(REPLACE(cc.codigo, '.', '')) + 1 as nivel,
                COALESCE(
                    (SELECT SUM(a.cargo - a.abono)
                     FROM auxiliares a
                     WHERE a.team_id = :team_id_anterior
                     AND a.codigo = cc.codigo
                     AND a.a_ejercicio = :ejercicio_anterior
                     AND a.a_periodo < :periodo_anterior
                    ), 0
                ) as saldo_anterior,
                COALESCE(
                    (SELECT SUM(a.cargo)
                     FROM auxiliares a
                     WHERE a.team_id = :team_id_periodo
                     AND a.codigo = cc.codigo
                     AND a.a_ejercicio = :ejercicio_periodo
                     AND a.a_periodo = :periodo_periodo
                    ), 0
                ) as cargos,
                COALESCE(
                    (SELECT SUM(a.abono)
                     FROM auxiliares a
                     WHERE a.team_id = :team_id_periodo2
                     AND a.codigo = cc.codigo
                     AND a.a_ejercicio = :ejercicio_periodo2
                     AND a.a_periodo = :periodo_periodo2
                    ), 0
                ) as abonos
            FROM cat_cuentas cc
            WHERE cc.team_id = :team_id
            AND EXISTS (
                SELECT 1 FROM auxiliares a
                WHERE a.team_id = cc.team_id
                AND a.codigo = cc.codigo
                AND a.a_ejercicio = :ejercicio_exists
                AND a.a_periodo <= :periodo_exists
            )
            ORDER BY cc.codigo
        ";

        $results = DB::select($query, [
            'team_id' => $team->id,
            'team_id_anterior' => $team->id,
            'ejercicio_anterior' => $this->ejercicio,
            'periodo_anterior' => $this->periodo,
            'team_id_periodo' => $team->id,
            'ejercicio_periodo' => $this->ejercicio,
            'periodo_periodo' => $this->periodo,
            'team_id_periodo2' => $team->id,
            'ejercicio_periodo2' => $this->ejercicio,
            'periodo_periodo2' => $this->periodo,
            'ejercicio_exists' => $this->ejercicio,
            'periodo_exists' => $this->periodo,
        ]);

        // Calcular saldo final y preparar datos
        $this->balanza = [];
        $totales = [
            'saldo_anterior' => 0,
            'cargos' => 0,
            'abonos' => 0,
            'saldo_deudor' => 0,
            'saldo_acreedor' => 0,
        ];

        foreach ($results as $row) {
            $saldoFinal = $row->saldo_anterior + $row->cargos - $row->abonos;

            $item = [
                'codigo' => $row->codigo,
                'cuenta' => $row->cuenta,
                'naturaleza' => $row->naturaleza,
                'nivel' => $row->nivel,
                'saldo_anterior' => (float) $row->saldo_anterior,
                'cargos' => (float) $row->cargos,
                'abonos' => (float) $row->abonos,
                'saldo_final' => $saldoFinal,
                'saldo_deudor' => $saldoFinal > 0 ? $saldoFinal : 0,
                'saldo_acreedor' => $saldoFinal < 0 ? abs($saldoFinal) : 0,
            ];

            $this->balanza[] = $item;

            // Acumular totales
            $totales['saldo_anterior'] += $item['saldo_anterior'];
            $totales['cargos'] += $item['cargos'];
            $totales['abonos'] += $item['abonos'];
            $totales['saldo_deudor'] += $item['saldo_deudor'];
            $totales['saldo_acreedor'] += $item['saldo_acreedor'];
        }

        $this->totales = $totales;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
