<?php

namespace App\Filament\Clusters\tiadmin\Resources\PagosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\PagosResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Pagos;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListPagos extends ListRecords
{
    use HasResizableColumn;
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
                    $emp = DatosFiscales::where('team_id',$this->id_empresa)->first();
                    $cli = Clientes::where('id',$record->cve_clie)->first();
                    return $emp->rfc.'_COMPROBANTE_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
        ];
    }
}
