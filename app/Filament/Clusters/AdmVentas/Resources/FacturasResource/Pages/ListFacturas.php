<?php

namespace App\Filament\Clusters\AdmVentas\Resources\FacturasResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\FacturasResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturasResource::class;

    public ?int $idorden;
    public ?int $id_empresa;
    protected function getHeaderActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_P')
            ->visible(false)
            ->print(false)
            ->savePdf()
            ->preview()
            ->margin([0,0,0,2])
            ->content(fn() => view('RepFactura',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
            ->modalWidth('7xl')
            ->filename(function () {
                $record = Facturas::where('id',$this->idorden)->first();
                $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                $cli = Clientes::where('id',$record->clie)->first();
                return $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
            })
        ];
    }

    public function callImprimir($record)
    {
        $tabla = $this->getTable();
        $tabla->getAction('Imprimir_Doc')->visible(true);
        $this->replaceMountedTableAction('Imprimir_Doc');
        $tabla->getAction('Imprimir_Doc')->visible(false);
    }
}
