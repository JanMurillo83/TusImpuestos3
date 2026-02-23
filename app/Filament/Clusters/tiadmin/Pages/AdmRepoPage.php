<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use App\Models\CatCuentas;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\EstadCXC_F;
use App\Models\Facturas;
use App\Models\Proveedores;
use App\Models\Inventario;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Support\Facades\DB;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Spatie\Browsershot\Browsershot;
use Torgodly\Html2Media\Actions\Html2MediaAction;
use function Termwind\style;

class AdmRepoPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-print';

    protected static string $view = 'filament.clusters.tiadmin.pages.adm-repo-page';

    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes';
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras', 'ventas']);
    }

    public ?string $ReportePDF = null;
    public function form(Form $form): Form
    {
        return $form
            ->schema([
               Actions::make([
                   Action::make('Saldo en Cartera')->extraAttributes(['style'=>'width:15rem !important'])
                   ->form([
                        DatePicker::make('fecha_inicio'),
                        DatePicker::make('fecha_fin'),
                        Select::make('cliente_id')->label('Cliente')
                        ->options(CatCuentas::where('team_id',Filament::getTenant()->id)
                            ->where('tipo','D')
                            ->where('acumula','10501000')
                            ->pluck('nombre','codigo'))->searchable()
                   ])
                   ->action(function($data){
                       if($data['cliente_id'] != null){
                           $ejercicio = Filament::getTenant()->ejercicio;
                           $periodo = Filament::getTenant()->periodo;
                           $team_id = Filament::getTenant()->id;
                           $empresa = Filament::getTenant()->name;
                           $clientes = EstadCXC_F::select(DB::raw("clave,cliente,sum(corriente) as corriente,sum(vencido) as vencido,sum(saldo) as saldo"))
                               ->groupBy('clave')->groupBy('cliente')->where('saldo','!=',0)->get();
                           $data = [
                               'empresa'=>$empresa,'team_id'=>$team_id,'ejercicio' => $ejercicio,
                               'periodo' => $periodo,'maindata'=>$clientes,
                               'saldo_corriente'=>$clientes->sum('corriente'),
                               'saldo_vencido'=>$clientes->sum('vencido'),
                               'saldo_total'=>$clientes->sum('saldo')
                           ];
                           $ruta = public_path().'/TMPCFDI/CXCGeneral_'.$team_id.'.pdf';
                           $html = \Illuminate\Support\Facades\View::make('filament.pages.estado-clientes-general', $data)->render();
                           Browsershot::html($html)->format('Letter')
                               ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                               ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                               ->noSandbox()
                               ->scale(0.8)->savePdf($ruta);
                           $this->ReportePDF = base64_encode(file_get_contents($ruta));
                           //return
                       }
                   }),
                   Action::make('Saldo Proveedores')->extraAttributes(['style'=>'width:15rem !important'])
                       ->form([
                           DatePicker::make('fecha_inicio'),
                           DatePicker::make('fecha_fin'),
                           Select::make('cliente_id')->label('Cliente')
                               ->options(CatCuentas::where('team_id',Filament::getTenant()->id)
                                   ->where('tipo','D')
                                   ->where('acumula','10501000')
                                   ->pluck('nombre','codigo'))->searchable()
                       ])
                       ->action(function(){
                           $this->team_id = Filament::getTenant()->id;
                           $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                           $this->fecha_fin = $data['fecha_fin'] ?? null;
                           $this->cliente_id = $data['cliente_id'] ?? null;
                           $this->getAction('SaldoProveedoresAction')->visible(true);
                           $this->replaceMountedAction('SaldoProveedoresAction');
                           $this->getAction('SaldoProveedoresAction')->visible(false);
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
                          ->default(Carbon::now()),
                      Select::make('serie')
                          ->label('Serie')
                          ->options(function(){
                              $series = \App\Models\SeriesFacturas::where('team_id',Filament::getTenant()->id)->pluck('serie','serie')->toArray();
                              return array_merge(['General'=>'General'],$series);
                          })
                          ->default('General')
                          ->required(),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->serie = $data['serie'] ?? 'General';
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $ruta = public_path().'/TMPCFDI/ResumenFacturacion_'.Filament::getTenant()->id.'.pdf';
                          if(\File::exists($ruta)) unlink($ruta);
                          /*$this->getAction('FacturacionAction')->visible(true);
                          $this->replaceMountedAction('FacturacionAction');
                          $this->getAction('FacturacionAction')->visible(false);*/
                          $data = [
                              'idempresa' => $this->team_id,
                              'inicial' => $this->fecha_inicio,
                              'final' => $this->fecha_fin,
                              'serie' => $this->serie,
                              'cliente_id' => $this->cliente_id,
                          ];
                          $html = \Illuminate\Support\Facades\View::make('ResumenFacturas', $data)->render();
                          Browsershot::html($html)->format('Letter')
                              ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                              ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                              ->noSandbox()
                              ->scale(0.8)->savePdf($ruta);
                          $this->ReportePDF = base64_encode(file_get_contents($ruta));
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
                  Action::make('Reporte Cotizaciones')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          $this->getAction('CotizacionesAction')->visible(true);
                          $this->replaceMountedAction('CotizacionesAction');
                          $this->getAction('CotizacionesAction')->visible(false);
                      }),
                  Action::make('Cotizaciones Excel')->icon('fas-file-excel')->color('success')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Exportar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          return $this->exportarCotizacionesExcel();
                      }),
                  Action::make('Vista Cotizaciones por Vendedor')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          $this->getAction('CotizacionesPorVendedorAction')->visible(true);
                          $this->replaceMountedAction('CotizacionesPorVendedorAction');
                          $this->getAction('CotizacionesPorVendedorAction')->visible(false);
                      }),
                  Action::make('Cotizaciones por Vendedor')->icon('fas-file-excel')->color('success')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Exportar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          return $this->exportarCotizacionesExcelPorVendedor();
                      }),
                  Action::make('Vista Cotizaciones por Cliente')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Generar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          $this->getAction('CotizacionesPorClienteAction')->visible(true);
                          $this->replaceMountedAction('CotizacionesPorClienteAction');
                          $this->getAction('CotizacionesPorClienteAction')->visible(false);
                      }),
                  Action::make('Cotizaciones por Cliente')->icon('fas-file-excel')->color('success')->form([
                      DatePicker::make('fecha_inicio')
                          ->label('Fecha Inicio'),
                      DatePicker::make('fecha_fin')
                          ->label('Fecha Fin'),
                      Select::make('cliente_id')
                          ->label('Cliente')
                          ->options(Clientes::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                          ->searchable()
                          ->placeholder('Todos')
                          ->native(false),
                      Select::make('estado')
                          ->label('Estado')
                          ->options([
                              'todas' => 'Todas',
                              'facturadas' => 'Facturadas',
                              'no_facturadas' => 'No Facturadas'
                          ])
                          ->default('todas')
                          ->required(),
                  ])->modalWidth('md')->modalSubmitActionLabel('Exportar')->extraAttributes(['style'=>'width:15rem !important'])
                      ->action(function($data){
                          $this->team_id = Filament::getTenant()->id;
                          $this->fecha_inicio = $data['fecha_inicio'] ?? null;
                          $this->fecha_fin = $data['fecha_fin'] ?? null;
                          $this->cliente_id = $data['cliente_id'] ?? null;
                          $this->estado_cotizacion = $data['estado'] ?? 'todas';
                          return $this->exportarCotizacionesExcelPorCliente();
                      }),
               ]),
                View::make('ReportesAdmin.your-pdf-viewer')
                    ->columnSpanFull()
                    ->viewData(function () {
                        return ['pdfBase64' => $this->ReportePDF ?? 'No se ha cargado'] ;
                    })->visible(function (){
                        if($this->ReportePDF == '') return false;
                        else return true;
                    }),
            ])->extraAttributes(['style'=>'margin-top:-5rem']);
    }

    public int $team_id;
    public $fecha_inicio;
    public $fecha_fin;
    public $cliente_id;
    public $proveedor_id;
    public $producto_id;
    public $serie;
    public $estado_cotizacion;
    public function getActions(): array
    {
        return [
            Html2MediaAction::make('SaldoCarteraAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Saldo de Clientes')
                ->content(fn() => view('ReporteClientesCXC',['id_empresa'=>$this->team_id,'fecha_inicio'=>$this->fecha_inicio,'fecha_fin'=>$this->fecha_fin,'cliente_id'=>$this->cliente_id]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('SaldoProveedoresAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Saldo de Proveedores')
                ->content(fn() => view('ReporteProveedoresCXP',['id_empresa'=>$this->team_id,'fecha_inicio'=>$this->fecha_inicio,'fecha_fin'=>$this->fecha_fin,'cliente_id'=>$this->cliente_id]))
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
                ->content(fn() => view('ResumenFacturas',[
                    'idempresa' => $this->team_id,
                    'inicial' => $this->fecha_inicio,
                    'final' => $this->fecha_fin,
                    'serie' => $this->serie,
                    'cliente_id' => $this->cliente_id,
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
                ->modalWidth('7xl'),
            Html2MediaAction::make('CotizacionesAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Reporte de Cotizaciones')
                ->content(fn() => view('ReporteCotizaciones',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                    'cliente_id' => $this->cliente_id,
                    'estado' => $this->estado_cotizacion,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('CotizacionesPorVendedorAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Reporte de Cotizaciones por Vendedor')
                ->content(fn() => view('ReporteCotizacionesPorVendedor',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                    'cliente_id' => $this->cliente_id,
                    'estado' => $this->estado_cotizacion,
                ]))
                ->modalWidth('7xl'),
            Html2MediaAction::make('CotizacionesPorClienteAction')
                ->visible(false)
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Reporte de Cotizaciones por Cliente')
                ->content(fn() => view('ReporteCotizacionesPorCliente',[
                    'team' => $this->team_id,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                    'cliente_id' => $this->cliente_id,
                    'estado' => $this->estado_cotizacion,
                ]))
                ->modalWidth('7xl')
        ];
    }

    public function exportarCotizacionesExcel()
    {
        $teamId = $this->team_id;
        $query = Cotizaciones::where('team_id', $teamId);

        if ($this->fecha_inicio && $this->fecha_fin) {
            $query->whereBetween(DB::raw('DATE(fecha)'), [$this->fecha_inicio, $this->fecha_fin]);
        }

        if ($this->cliente_id) {
            $query->where('clie', $this->cliente_id);
        }

        $cotizaciones = $query->orderBy('fecha', 'desc')->get();

        $estado = $this->estado_cotizacion ?? 'todas';
        if ($estado == 'facturadas') {
            $cotizaciones = $cotizaciones->filter(fn($c) => Facturas::where('cotizacion_id', $c->id)->exists());
        } elseif ($estado == 'no_facturadas') {
            $cotizaciones = $cotizaciones->filter(fn($c) => !Facturas::where('cotizacion_id', $c->id)->exists());
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Cotización', 'Fecha', 'Cliente', 'Moneda', 'T.Cambio', 'Subtotal USD', 'IVA USD', 'Total USD', 'Subtotal MXN', 'IVA MXN', 'Total MXN', 'Estado Facturación', 'Factura'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Estilo encabezados
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($cotizaciones as $cotizacion) {
            $tcambio = floatval($cotizacion->tcambio) != 0 ? $cotizacion->tcambio : 1;

            if ($cotizacion->moneda == 'USD') {
                $sub_usd = $cotizacion->subtotal;
                $iva_usd = $cotizacion->iva;
                $tot_usd = $cotizacion->total;
                $sub_mxn = $cotizacion->subtotal * $tcambio;
                $iva_mxn = $cotizacion->iva * $tcambio;
                $tot_mxn = $cotizacion->total * $tcambio;
            } else {
                $sub_usd = 0;
                $iva_usd = 0;
                $tot_usd = 0;
                $sub_mxn = $cotizacion->subtotal;
                $iva_mxn = $cotizacion->iva;
                $tot_mxn = $cotizacion->total;
            }

            $factura = Facturas::where('cotizacion_id', $cotizacion->id)->first();
            $estadoFact = $factura ? 'Facturada' : 'No Facturada';
            $doctoFact = $factura ? $factura->docto : '-';

            $sheet->setCellValue('A' . $row, $cotizacion->docto);
            $sheet->setCellValue('B' . $row, Carbon::create($cotizacion->fecha)->format('Y-m-d'));
            $sheet->setCellValue('C' . $row, $cotizacion->nombre);
            $sheet->setCellValue('D' . $row, $cotizacion->moneda ?? 'MXN');
            $sheet->setCellValue('E' . $row, $tcambio);
            $sheet->setCellValue('F' . $row, $sub_usd);
            $sheet->setCellValue('G' . $row, $iva_usd);
            $sheet->setCellValue('H' . $row, $tot_usd);
            $sheet->setCellValue('I' . $row, $sub_mxn);
            $sheet->setCellValue('J' . $row, $iva_mxn);
            $sheet->setCellValue('K' . $row, $tot_mxn);
            $sheet->setCellValue('L' . $row, $estadoFact);
            $sheet->setCellValue('M' . $row, $doctoFact);
            $row++;
        }

        // Formato numérico para columnas de importes
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('E2:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Auto-ajustar ancho de columnas
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Reporte_Cotizaciones_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        Notification::make()->title('Cotizaciones exportadas a Excel')->success()->send();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function getCotizacionesFiltradas()
    {
        $query = Cotizaciones::where('team_id', $this->team_id);

        if ($this->fecha_inicio && $this->fecha_fin) {
            $query->whereBetween(DB::raw('DATE(fecha)'), [$this->fecha_inicio, $this->fecha_fin]);
        }

        if ($this->cliente_id) {
            $query->where('clie', $this->cliente_id);
        }

        $cotizaciones = $query->orderBy('fecha', 'desc')->get();

        $estado = $this->estado_cotizacion ?? 'todas';
        if ($estado == 'facturadas') {
            $cotizaciones = $cotizaciones->filter(fn($c) => Facturas::where('cotizacion_id', $c->id)->exists());
        } elseif ($estado == 'no_facturadas') {
            $cotizaciones = $cotizaciones->filter(fn($c) => !Facturas::where('cotizacion_id', $c->id)->exists());
        }

        return $cotizaciones;
    }

    private function writeExcelHeaders($sheet, $headers, $styleRange)
    {
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        ];
        $sheet->getStyle($styleRange)->applyFromArray($headerStyle);
    }

    private function calcCotizacionImportes($cotizacion)
    {
        $tcambio = floatval($cotizacion->tcambio) != 0 ? $cotizacion->tcambio : 1;
        if ($cotizacion->moneda == 'USD') {
            return [
                'sub_usd' => $cotizacion->subtotal, 'iva_usd' => $cotizacion->iva, 'tot_usd' => $cotizacion->total,
                'sub_mxn' => $cotizacion->subtotal * $tcambio, 'iva_mxn' => $cotizacion->iva * $tcambio, 'tot_mxn' => $cotizacion->total * $tcambio,
                'tcambio' => $tcambio,
            ];
        }
        return [
            'sub_usd' => 0, 'iva_usd' => 0, 'tot_usd' => 0,
            'sub_mxn' => $cotizacion->subtotal, 'iva_mxn' => $cotizacion->iva, 'tot_mxn' => $cotizacion->total,
            'tcambio' => $tcambio,
        ];
    }

    private function writeCotizacionRow($sheet, $row, $cotizacion, $importes)
    {
        $factura = Facturas::where('cotizacion_id', $cotizacion->id)->first();
        $sheet->setCellValue('A' . $row, $cotizacion->docto);
        $sheet->setCellValue('B' . $row, Carbon::create($cotizacion->fecha)->format('Y-m-d'));
        $sheet->setCellValue('C' . $row, $cotizacion->nombre);
        $sheet->setCellValue('D' . $row, $cotizacion->moneda ?? 'MXN');
        $sheet->setCellValue('E' . $row, $importes['tcambio']);
        $sheet->setCellValue('F' . $row, $importes['sub_usd']);
        $sheet->setCellValue('G' . $row, $importes['iva_usd']);
        $sheet->setCellValue('H' . $row, $importes['tot_usd']);
        $sheet->setCellValue('I' . $row, $importes['sub_mxn']);
        $sheet->setCellValue('J' . $row, $importes['iva_mxn']);
        $sheet->setCellValue('K' . $row, $importes['tot_mxn']);
        $sheet->setCellValue('L' . $row, $factura ? 'Facturada' : 'No Facturada');
        $sheet->setCellValue('M' . $row, $factura ? $factura->docto : '-');
    }

    private function writeSubtotalRow($sheet, $row, $label, $totals)
    {
        $subtotalStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']],
        ];
        $sheet->setCellValue('A' . $row, $label);
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, $totals['sub_usd']);
        $sheet->setCellValue('G' . $row, $totals['iva_usd']);
        $sheet->setCellValue('H' . $row, $totals['tot_usd']);
        $sheet->setCellValue('I' . $row, $totals['sub_mxn']);
        $sheet->setCellValue('J' . $row, $totals['iva_mxn']);
        $sheet->setCellValue('K' . $row, $totals['tot_mxn']);
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($subtotalStyle);
        $sheet->getStyle('F' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function writeGranTotalRow($sheet, $row, $totals)
    {
        $grandStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F5496']],
        ];
        $sheet->setCellValue('A' . $row, 'GRAN TOTAL');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, $totals['sub_usd']);
        $sheet->setCellValue('G' . $row, $totals['iva_usd']);
        $sheet->setCellValue('H' . $row, $totals['tot_usd']);
        $sheet->setCellValue('I' . $row, $totals['sub_mxn']);
        $sheet->setCellValue('J' . $row, $totals['iva_mxn']);
        $sheet->setCellValue('K' . $row, $totals['tot_mxn']);
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($grandStyle);
        $sheet->getStyle('F' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function finalizarExcel($spreadsheet, $sheet, $lastDataRow, $fileName)
    {
        if ($lastDataRow >= 2) {
            $sheet->getStyle('E2:K' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        Notification::make()->title('Cotizaciones exportadas a Excel')->success()->send();
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportarCotizacionesExcelPorVendedor()
    {
        $cotizaciones = $this->getCotizacionesFiltradas();
        $grouped = $cotizaciones->groupBy(fn($c) => $c->nombre_elaboro ?: 'Sin vendedor');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Cotización', 'Fecha', 'Cliente', 'Moneda', 'T.Cambio', 'Subtotal USD', 'IVA USD', 'Total USD', 'Subtotal MXN', 'IVA MXN', 'Total MXN', 'Estado Facturación', 'Factura'];
        $this->writeExcelHeaders($sheet, $headers, 'A1:M1');

        $row = 2;
        $grandTotals = ['sub_usd' => 0, 'iva_usd' => 0, 'tot_usd' => 0, 'sub_mxn' => 0, 'iva_mxn' => 0, 'tot_mxn' => 0];

        foreach ($grouped as $vendedor => $items) {
            // Encabezado de grupo vendedor
            $groupStyle = [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B4C6E7']],
            ];
            $sheet->setCellValue('A' . $row, 'Vendedor: ' . $vendedor);
            $sheet->mergeCells('A' . $row . ':M' . $row);
            $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($groupStyle);
            $row++;

            $segTotals = ['sub_usd' => 0, 'iva_usd' => 0, 'tot_usd' => 0, 'sub_mxn' => 0, 'iva_mxn' => 0, 'tot_mxn' => 0];
            foreach ($items as $cotizacion) {
                $imp = $this->calcCotizacionImportes($cotizacion);
                $this->writeCotizacionRow($sheet, $row, $cotizacion, $imp);
                foreach (['sub_usd', 'iva_usd', 'tot_usd', 'sub_mxn', 'iva_mxn', 'tot_mxn'] as $k) {
                    $segTotals[$k] += $imp[$k];
                    $grandTotals[$k] += $imp[$k];
                }
                $row++;
            }
            $this->writeSubtotalRow($sheet, $row, 'Subtotal ' . $vendedor . ' (' . count($items) . ' cotizaciones)', $segTotals);
            $row += 2;
        }

        $this->writeGranTotalRow($sheet, $row, $grandTotals);
        return $this->finalizarExcel($spreadsheet, $sheet, $row, 'Cotizaciones_por_Vendedor_' . date('Y-m-d_His') . '.xlsx');
    }

    public function exportarCotizacionesExcelPorCliente()
    {
        $cotizaciones = $this->getCotizacionesFiltradas();
        $grouped = $cotizaciones->groupBy(fn($c) => $c->nombre ?: 'Sin cliente');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Cotización', 'Fecha', 'Cliente', 'Moneda', 'T.Cambio', 'Subtotal USD', 'IVA USD', 'Total USD', 'Subtotal MXN', 'IVA MXN', 'Total MXN', 'Estado Facturación', 'Factura'];
        $this->writeExcelHeaders($sheet, $headers, 'A1:M1');

        $row = 2;
        $grandTotals = ['sub_usd' => 0, 'iva_usd' => 0, 'tot_usd' => 0, 'sub_mxn' => 0, 'iva_mxn' => 0, 'tot_mxn' => 0];

        foreach ($grouped as $cliente => $items) {
            $groupStyle = [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B4C6E7']],
            ];
            $sheet->setCellValue('A' . $row, 'Cliente: ' . $cliente);
            $sheet->mergeCells('A' . $row . ':M' . $row);
            $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($groupStyle);
            $row++;

            $segTotals = ['sub_usd' => 0, 'iva_usd' => 0, 'tot_usd' => 0, 'sub_mxn' => 0, 'iva_mxn' => 0, 'tot_mxn' => 0];
            foreach ($items as $cotizacion) {
                $imp = $this->calcCotizacionImportes($cotizacion);
                $this->writeCotizacionRow($sheet, $row, $cotizacion, $imp);
                foreach (['sub_usd', 'iva_usd', 'tot_usd', 'sub_mxn', 'iva_mxn', 'tot_mxn'] as $k) {
                    $segTotals[$k] += $imp[$k];
                    $grandTotals[$k] += $imp[$k];
                }
                $row++;
            }
            $this->writeSubtotalRow($sheet, $row, 'Subtotal ' . $cliente . ' (' . count($items) . ' cotizaciones)', $segTotals);
            $row += 2;
        }

        $this->writeGranTotalRow($sheet, $row, $grandTotals);
        return $this->finalizarExcel($spreadsheet, $sheet, $row, 'Cotizaciones_por_Cliente_' . date('Y-m-d_His') . '.xlsx');
    }
}
