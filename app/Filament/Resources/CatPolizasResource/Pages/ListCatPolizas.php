<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListCatPolizas extends ListRecords
{
    protected static string $resource = CatPolizasResource::class;
    public function mount(): void
    {
        $this->SetTotales();
    }

    public function SetTotales()
    {
        $polizas = CatPolizas::where('team_id',Filament::getTenant()->id)
            ->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->get();
        foreach ($polizas as $poliza) {
            $cargos = 0;
            $abonos = 0;
            $auxiliar = Auxiliares::where('cat_polizas_id',$poliza->id)->get();
            foreach ($auxiliar as $auxiliar) {
                $cargos += $auxiliar->cargo;
                $abonos += $auxiliar->abono;
            }
            $poliza->cargos = $cargos;
            $poliza->abonos = $abonos;
            $poliza->save();
        }
    }
    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function getTabs(): array
    {
        //$tabs = ['all' => Tab::make('All')->badge($this->getModel()::count())];
        $tabs = [];
        $tabs['TD'] = Tab::make('Todas');
        $tabs['PG'] = Tab::make('Gastos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'PG');
        });
        $tabs['PV'] = Tab::make('Ventas')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'PV');
        });
        $tabs['Dr'] = Tab::make('Diario')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Dr');
        });
        $tabs['Ig'] = Tab::make('Ingresos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Ig');
        });
        $tabs['Eg'] = Tab::make('Egresos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Eg');
        });

        return $tabs;
    }
}
