<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListCatPolizas extends ListRecords
{
    protected static string $resource = CatPolizasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->createAnother(false)
            ->label('Agregar')
            ->icon('fas-plus')
            ->modalSubmitActionLabel('Grabar')
            ->modalWidth('7xl'),
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
