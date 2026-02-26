<?php

namespace App\Filament\Clusters\tiadmin\Resources\NotasdeCreditoResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\NotasdeCreditoResource;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\NotadeCredito;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ListNotasdeCreditos extends ListRecords
{
    protected static string $resource = NotasdeCreditoResource::class;
    public ?int $idorden;
    public ?int $id_empresa;
    protected function getHeaderActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_P')
                ->visible(fn($livewire) => $livewire->idorden !== null)
                ->print(false)
                ->savePdf()
                ->preview()
                ->margin([0,0,0,2])
                ->content(fn() => view('RepNotacred',['idorden'=>$this->idorden,'id_empresa'=>$this->id_empresa]))
                ->modalWidth('7xl')
                ->filename(function () {
                    $record = NotadeCredito::where('id',$this->idorden)->first();
                    $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    return $emp->rfc.'_NOTACREDITO_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                }),
        ];
    }
}
