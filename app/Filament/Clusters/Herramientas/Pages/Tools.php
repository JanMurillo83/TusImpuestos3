<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class Tools extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.herramientas.pages.tools';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Herramientas';

    protected static function setShouldRegisterNavigation(): bool
    {
        if(auth()->id() == 1) return true;
        return false;
    }

    public ? int $periodo;
    public ? int $ejercicio;
    protected function getActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(true)
                ->margin([0,0,0,2])
                ->content(fn() => view('ReporteTiembres',['periodo'=>$this->periodo,'ejercicio'=>$this->ejercicio]))
                ->modalWidth('7xl')
                ->filename('Reporte de Timbres')
        ];
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Actions::make([
                    Actions\Action::make('Reporte de Timbres')
                    ->form([
                        Select::make('periodo')
                        ->options([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'])
                        ->default(Carbon::now()->month),
                        Select::make('ejercicio')
                        ->options([2024=>2024,2025=>2025,2026=>2026,2027=>2027,2028=>2028,2029=>2029,2030=>2030])
                        ->default(Carbon::now()->year),
                    ])
                    ->action(function ($livewire,array $data){
                        //dd($data);
                        $this->periodo = intval($data['periodo']);
                        $this->ejercicio = intval($data['ejercicio']);
                        $livewire->getAction('Imprimir_Doc_E')->visible(true);
                        $livewire->replaceMountedAction('Imprimir_Doc_E');
                        $livewire->getAction('Imprimir_Doc_E')->visible(false);
                    })
                ])
            ]);
    }

}
