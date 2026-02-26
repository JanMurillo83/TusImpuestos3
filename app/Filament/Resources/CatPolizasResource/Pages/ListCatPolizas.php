<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\TableSettings;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListCatPolizas extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = CatPolizasResource::class;

    protected function persistColumnWidthsToDatabase(): void
    {
        // Your custom database save logic here
        TableSettings::updateOrCreate(
            [
                'user_id' => $this->getUserId(),
                'resource' => $this->getResourceModelFullPath(), // e.g., 'App\Models\User'
                'team_id' => Filament::getTenant()->id,
            ],
            ['settings' => $this->columnWidths]
        );
    }
    public function mount(): void
    {
        if (blank($this->activeTab)) {
            $this->activeTab = 'Todas';
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
        return [
            'Todas'=>Tab::make('Todas')->modifyQueryUsing(function ($query){
                return $query->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
            }),
            'PG'=>Tab::make('Gastos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'PG')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'PV'=>Tab::make('Ventas')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'PV')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Dr'=>Tab::make('Diario')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Dr')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Ig'=>Tab::make('Ingresos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Ig')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Eg'=>Tab::make('Egresos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Eg')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'OP'=>Tab::make('Otros Periodos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('ejercicio',Filament::getTenant()->ejercicio);
                })
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'Todas';

    }


}
