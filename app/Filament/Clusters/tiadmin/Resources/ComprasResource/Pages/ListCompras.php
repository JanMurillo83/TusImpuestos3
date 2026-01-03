<?php

namespace App\Filament\Clusters\tiadmin\Resources\ComprasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ComprasResource;
use App\Models\Compras;
use App\Models\DatosFiscales;
use App\Models\Ordenes;
use App\Models\Proveedores;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListCompras extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ComprasResource::class;
    public ?int $idorden;
    public ?int $id_empresa;
    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(true)
                ->margin([0,0,0,2])
                ->content(fn() => view('RepCompra',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->filename(function () {
                    $record = Compras::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$this->id_empresa)->first();
                    $cli = Proveedores::where('id',$record->prov)->first();
                    return $emp->rfc.'_COMPRA_'.$record->folio.'_'.$cli->rfc.'.pdf';
                })
        ];
    }
}
