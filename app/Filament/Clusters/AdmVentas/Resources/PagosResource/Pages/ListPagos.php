<?php

namespace App\Filament\Clusters\AdmVentas\Resources\PagosResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\PagosResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Pagos;
use App\Models\Team;
use Filament\Actions;
use Filament\Facades\Filament;
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
                    $record = Pagos::where('id',$this->idorden)->first();
                    $emp = Team::where('id',Filament::getTenant()->id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    return $emp->taxid.'_COMPROBANTE_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
        ];
    }
}
