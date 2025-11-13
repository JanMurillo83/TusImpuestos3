<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesController;
use App\Livewire\Indicadores2Widget;
use App\Livewire\Indicadores3Widget;
use App\Livewire\Indicadores4Widget;
use App\Livewire\IndicadoresWidget;
use App\Models\Auxiliares;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Split;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class IndicadoresMain extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-chart-simple';
    protected static ?string $title = 'Indicadores del Periodo';
    protected static string $view = 'filament.pages.indicadores-main';

    protected ?string $maxContentWidth = 'full';
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return '';
    }
    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $aux =Auxiliares::where('team_id',Filament::getTenant()->id)->where('a_ejercicio',$ejercicio)->where('a_periodo',$periodo)->get();
        if(count($aux)>0) (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
    }

    public function form(\Filament\Forms\Form $form) :\Filament\Forms\Form
    {
        return $form
            ->schema([
                Split::make([
                    Fieldset::make('Indicadores Contables')
                        ->schema([
                            Livewire::make(IndicadoresWidget::class),
                            Livewire::make(Indicadores2Widget::class),
                        ])->columnSpanFull(),
                    Fieldset::make('Indicadores Administrativos')
                        ->schema([
                            Livewire::make(Indicadores3Widget::class),
                            Livewire::make(Indicadores4Widget::class),
                        ])->columnSpanFull(),
                ])->columnSpanFull(),

                Actions::make([
                    Actions\Action::make('Actualizar')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            $aux =Auxiliares::where('team_id',Filament::getTenant()->id)->where('a_ejercicio',$ejercicio)->where('a_periodo',$periodo)->get();
                            if(count($aux)>0) (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
                        })
                ])
            ]);
    }
}
