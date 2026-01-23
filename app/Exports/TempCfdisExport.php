<?php

namespace App\Exports;

use App\Models\TempCfdis;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TempCfdisExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected int $team_id;

    public function __construct(int $team_id)
    {
        $this->team_id = $team_id;
    }

    public function collection()
    {
        return TempCfdis::where('team_id', $this->team_id)->get();
    }

    public function headings(): array
    {
        return [
            'UUID',
            'RFC Emisor',
            'Nombre Emisor',
            'RFC Receptor',
            'Nombre Receptor',
            'RFC PAC',
            'Fecha Emisión',
            'Fecha Certificación SAT',
            'Monto',
            'Efecto Comprobante',
            'Estatus',
            'Fecha Cancelación',
            'Tipo'
        ];
    }

    public function map($record): array
    {
        return [
            $record->UUID,
            $record->RfcEmisor,
            $record->NombreEmisor,
            $record->RfcReceptor,
            $record->NombreReceptor,
            $record->RfcPac,
            Carbon::parse($record->FechaEmision)->format('d-m-Y'),
            $record->FechaCertificacionSat,
            $record->Monto,
            $record->EfectoComprobante,
            $record->Estatus,
            $record->FechaCancelacion,
            $record->Tipo
        ];
    }
}
