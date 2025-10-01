<?php

namespace App\Filament\Clusters\AdmReportes\Pages;

use App\Filament\Clusters\AdmReportes;
use App\Models\Clientes;
use App\Models\Proveedores;
use App\Models\Inventario;
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
                   Action::make('Estado Cuenta Cliente')->form([
                       Select::make('cliente_id')
                           ->label('Cliente')
                           ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                           ->searchable()
                           ->required(),
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()->startOfMonth()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->cliente_id = $data['cliente_id'];
                           $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                           $this->fecha_fin = $data['fecha_fin'] ?? null;
                           $this->getAction('EstadoCuentaClienteAction')->visible(true);
                           $this->replaceMountedAction('EstadoCuentaClienteAction');
                           $this->getAction('EstadoCuentaClienteAction')->visible(false);
                       }),
                   Action::make('Estado Cuenta Clientes')->form([
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()->startOfMonth()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                           $this->fecha_fin = $data['fecha_fin'] ?? null;
                           $this->getAction('EstadoCuentaClientesAction')->visible(true);
                           $this->replaceMountedAction('EstadoCuentaClientesAction');
                           $this->getAction('EstadoCuentaClientesAction')->visible(false);
                       }),
                   Action::make('Estado Cuenta Proveedor')->form([
                       Select::make('proveedor_id')
                           ->label('Proveedor')
                           ->options(Proveedores::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                           ->searchable()
                           ->required(),
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()->startOfMonth()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->proveedor_id = $data['proveedor_id'];
                           $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                           $this->fecha_fin = $data['fecha_fin'] ?? null;
                           $this->getAction('EstadoCuentaProveedorAction')->visible(true);
                           $this->replaceMountedAction('EstadoCuentaProveedorAction');
                           $this->getAction('EstadoCuentaProveedorAction')->visible(false);
                       }),
                   Action::make('Estado Cuenta Proveedores')->form([
                       DatePicker::make('fecha_inicio')
                           ->label('Fecha Inicio')
                           ->default(Carbon::now()->startOfMonth()),
                       DatePicker::make('fecha_fin')
                           ->label('Fecha Fin')
                           ->default(Carbon::now())
                   ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                       ->action(function($data){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                           $this->fecha_fin = $data['fecha_fin'] ?? null;
                           $this->getAction('EstadoCuentaProveedoresAction')->visible(true);
                           $this->replaceMountedAction('EstadoCuentaProveedoresAction');
                           $this->getAction('EstadoCuentaProveedoresAction')->visible(false);
                       }),
                   Action::make('Movimientos Inventario')->form([
                      Select::make('producto_id')
                          ->label('Producto')
                          ->options(Inventario::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio')
                          ->default(Carbon::now()->startOfMonth()),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin')
                          ->default(Carbon::now())
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->producto_id = $data['producto_id'] ?? null;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->getAction('MovimientosInventarioAction')->visible(true);
                          $this->replaceMountedAction('MovimientosInventarioAction');
                          $this->getAction('MovimientosInventarioAction')->visible(false);
                      }),
                  Action::make('Reporte Facturación')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio')
                          ->default(Carbon::now()->startOfMonth()),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin')
                          ->default(Carbon::now())
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->getAction('FacturacionAction')->visible(true);
                          $this->replaceMountedAction('FacturacionAction');
                          $this->getAction('FacturacionAction')->visible(false);
                      }),
                  Action::make('Reporte Compras')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio')
                          ->default(Carbon::now()->startOfMonth()),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin')
                          ->default(Carbon::now())
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->getAction('ComprasAction')->visible(true);
                          $this->replaceMountedAction('ComprasAction');
                          $this->getAction('ComprasAction')->visible(false);
                      }),
                  Action::make('Costo Inventario')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function(){
                          $this->team_id = Filament::getTenant()->id;
                          $this->getAction('CostoInventarioAction')->visible(true);
                          $this->replaceMountedAction('CostoInventarioAction');
                          $this->getAction('CostoInventarioAction')->visible(false);
                      }),
               ])
            ])->extraAttributes(['style'=>'margin-top:-5rem']);
    }

    public int $team_id;
    public $fecha_inicio;
    public $fecha_fin;
    public $cliente_id;
    public $proveedor_id;
    public $producto_id;
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
                ->modalWidth('7xl'),
            Html2MediaAction::make('EstadoCuentaClienteAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Cuenta del Cliente')
                ->content(fn() => view('EstadoCuentaCliente',[
                    'team' => $this->team_id,
                    'cliente_id' => $this->cliente_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('EstadoCuentaClientesAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Cuenta de Clientes')
                ->content(fn() => view('EstadoCuentaClientes',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('EstadoCuentaProveedorAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Cuenta del Proveedor')
                ->content(fn() => view('EstadoCuentaProveedor',[
                    'team' => $this->team_id,
                    'proveedor_id' => $this->proveedor_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('EstadoCuentaProveedoresAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Cuenta de Proveedores')
                ->content(fn() => view('EstadoCuentaProveedores',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('FacturacionAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Reporte de Facturación')
                ->content(fn() => view('RepFacturacion',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('ComprasAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Reporte de Compras')
                ->content(fn() => view('RepCompras',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('CostoInventarioAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Costo del Inventario')
                ->content(fn() => view('CostoInventario',[
                    'team' => $this->team_id,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('MovimientosInventarioAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Movimientos de Inventario')
                ->content(fn() => view('MovimientosInventario',[
                    'team' => $this->team_id,
                    'producto_id' => $this->producto_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                ]))
                ->modalWidth('7xl')
        ];
    }
}
