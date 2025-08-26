<?php

namespace App\Filament\Clusters\AdmVentas\Resources\FacturasResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\FacturasResource;
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
            ->margin([10,10,10,10])
            ->content(fn() => view('RepFactura',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
            ->modalWidth('7xl')
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
