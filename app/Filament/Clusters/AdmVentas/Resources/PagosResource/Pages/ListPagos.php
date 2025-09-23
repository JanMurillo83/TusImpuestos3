<?php

namespace App\Filament\Clusters\AdmVentas\Resources\PagosResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\PagosResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListPagos extends ListRecords
{
    protected static string $resource = PagosResource::class;
    public ?int $idorden;
    public ?int $id_empresa;
    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Html2MediaAction::make('Imprimir_Doc_P')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview()
                ->margin([0,0,0,2])
                ->content(fn() => view('RepFacturaCP',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->filename(function () {
                    $record = Facturas::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    return $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
        ];
    }
}
