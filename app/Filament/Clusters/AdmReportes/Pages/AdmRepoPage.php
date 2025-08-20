<?php

namespace App\Filament\Clusters\AdmReportes\Pages;

use App\Filament\Clusters\AdmReportes;
use Carbon\Carbon;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Tables\Enums\ActionsPosition;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Torgodly\Html2Media\Actions\Html2MediaAction;
use function Termwind\style;

class AdmRepoPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-print';

    protected static string $view = 'filament.clusters.adm-reportes.pages.adm-repo-page';

    protected static ?string $cluster = AdmReportes::class;
    protected static ?string $title = 'Reportes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
               Actions::make([
                   Action::make('Saldo en Cartera')->extraAttributes(['style'=>'width:15rem !important'])
                   ->action(function(){
                       $this->team_id = Filament::getTenant()->id;
                       $this->getAction('SaldoCarteraAction')->visible(true);
                       $this->replaceMountedAction('SaldoCarteraAction');
                       $this->getAction('SaldoCarteraAction')->visible(false);
                   }),
                   Action::make('Saldo Proveedores')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function(){
                           $this->team_id = Filament::getTenant()->id;
                           $this->getAction('SaldoProveedoresAction')->visible(true);
                           $this->replaceMountedAction('SaldoProveedoresAction');
                           $this->getAction('SaldoProveedoresAction')->visible(false);
                       }),
                   Action::make('Costo Inventario')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function(){
                           $this->team_id = Filament::getTenant()->id;
                           $this->getAction('SaldoCarteraAction')->visible(true);
                           $this->replaceMountedAction('SaldoCarteraAction');
                           $this->getAction('SaldoCarteraAction')->visible(false);
                       }),
                   Action::make('Ventas')->form([
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())

                   ])->modalWidth('sm')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'];
                           $this->fecha_fin = $data['fecha_fin'];
                           $this->getAction('SaldoCarteraAction')->visible(true);
                           $this->replaceMountedAction('SaldoCarteraAction');
                           $this->getAction('SaldoCarteraAction')->visible(false);
                       }),
                   Action::make('Compras')->form([
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('sm')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'];
                           $this->fecha_fin = $data['fecha_fin'];
                           $this->getAction('SaldoCarteraAction')->visible(true);
                           $this->replaceMountedAction('SaldoCarteraAction');
                           $this->getAction('SaldoCarteraAction')->visible(false);
                       }),
                   Action::make('Movimientos al inventario')->form([
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('sm')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'];
                           $this->fecha_fin = $data['fecha_fin'];
                           $this->getAction('SaldoCarteraAction')->visible(true);
                           $this->replaceMountedAction('SaldoCarteraAction');
                           $this->getAction('SaldoCarteraAction')->visible(false);
                       }),
               ])
            ])->extraAttributes(['style'=>'margin-top:-5rem']);
    }

    public int $team_id;
    public $fecha_inicio;
    public $fecha_fin;
    public function getActions(): array
    {
        return [
            Html2MediaAction::make('SaldoCarteraAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Saldo de Clientes')
                ->content(fn() => view('SaldosClientes',['team'=>$this->team_id]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('SaldoProveedoresAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Saldo de Proveedores')
                ->content(fn() => view('SaldosProveedores',['team'=>$this->team_id]))
                ->modalWidth('7xl')
        ];
    }
}
