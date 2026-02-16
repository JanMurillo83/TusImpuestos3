<?php

namespace App\Exports;

use App\Models\EstadCXP_F;
use App\Models\Proveedores;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadoProveedoresExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $team_id;
    protected $ejercicio;
    protected $periodo;

    public function __construct($team_id, $ejercicio, $periodo)
    {
        $this->team_id = $team_id;
        $this->ejercicio = $ejercicio;
        $this->periodo = $periodo;
    }

    public function collection()
    {
        return EstadCXP_F::select(DB::raw("clave,cliente,sum(corriente) as corriente,sum(vencido) as vencido,sum(saldo) as saldo"))
            ->groupBy('clave')
            ->groupBy('cliente')
            ->where('saldo','!=',0)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Cuenta',
            'Proveedor',
            'RFC',
            'Límite de Crédito',
            'Saldo Total',
            'Saldo Vencido',
            'Saldo Por Vencer',
            '% del Total'
        ];
    }

    public function map($row): array
    {
        $proveedor = Proveedores::where('cuenta_contable', $row->clave)->first();
        $total = EstadCXP_F::where('saldo','!=',0)->sum('saldo');
        $porcentaje = $total > 0 ? ($row->saldo * 100 / $total) : 0;

        return [
            $row->clave,
            $row->cliente,
            $proveedor?->rfc ?? 'XAXX010101000',
            $proveedor?->limite_credito ?? 0,
            $row->saldo,
            $row->vencido,
            $row->corriente,
            $porcentaje
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Estado de Proveedores';
    }
}
