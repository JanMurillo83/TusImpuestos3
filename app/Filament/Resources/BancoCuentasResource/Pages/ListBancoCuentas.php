<?php

namespace App\Filament\Resources\BancoCuentasResource\Pages;

use App\Filament\Resources\BancoCuentasResource;
use App\Models\CatCuentas;
use App\Models\Saldosbanco;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListBancoCuentas extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = BancoCuentasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*Actions\CreateAction::make()
            ->label('Agregar')
            ->icon('fas-plus')
            ->createAnother(false)
            ->after(function(Model $record,$data){
                $ctadata = ['codigo'=>$data['codigo'], 'nombre'=>$data['banco'], 'acumula'=>'10200000', 'tipo'=>'D', 'naturaleza'=>'D', 'csat'=>'102.01', 'team_id'=>Filament::getTenant()->id];
                CatCuentas::insert($ctadata);
                $ban = $record->getKey();
                $bandata = [
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>1],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>2],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>3],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>4],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>5],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>6],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>7],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>8],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>9],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>10],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>11],
                    ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>12]];
                Saldosbanco::insert($bandata);
            }),*/
        ];
    }
}
