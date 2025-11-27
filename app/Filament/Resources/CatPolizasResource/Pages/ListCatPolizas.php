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
                if($auxiliar->codigo == '10201000'&&$auxiliar->cuenta == '10201000'){
                    $cta = CatCuentas::where('codigo','10201000')->where('team_id',Filament::getTenant()->id)->first();
                    $auxiliar->cuenta = $cta->nombre;
                    $auxiliar->save();
                }
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
        $tabs['TD'] = Tab::make('Todas')->modifyQueryUsing(function ($query){
            return $query->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['PG'] = Tab::make('Gastos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'PG')->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['PV'] = Tab::make('Ventas')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'PV')->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['Dr'] = Tab::make('Diario')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Dr')->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['Ig'] = Tab::make('Ingresos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Ig')->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['Eg'] = Tab::make('Egresos')
        ->modifyQueryUsing(function ($query){
            return $query->where('tipo', 'Eg')->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
        });
        $tabs['OP'] = Tab::make('Otros Periodos')
            ->modifyQueryUsing(function ($query){
                return $query->where('ejercicio',Filament::getTenant()->ejercicio);
            });

        return $tabs;
    }

    public function getDefaultActiveTab(): string
    {
        return 'Todas';

    }

}
