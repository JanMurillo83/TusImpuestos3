<?php

namespace App\Filament\Pages;

use App\Models\Auxiliares;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Spatie\Browsershot\Browsershot;

class ReportesAdmin extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes Administrativos';
    protected static ?string $pluralLabel = 'Reportes Administrativos';
    protected static string $view = 'filament.pages.reportes-admin';
    public ?string $reporte;
    public ?string $fecha_inicial;
    public ?string $fecha_final;
    public ?string $cliente;
    public ?string $reporte_url;
    public ?string $reporte_url_path;
    public ?string $reporte_generado;
    public ?string $reporte_generado_2;
    public ?string $ReportePDF = '';
    public function mount():void
    {
        $id = Filament::getTenant()->id;
        $this->reporte_url_path = 'storage/TMPREPO/'.$id;
        //dd($this->reporte_url_path);
        \File::makeDirectory($this->reporte_url_path, 0777,true,true);
        //dd(Storage::url('dummy.pdf'));
        $this->reporte_url = $this->reporte_url_path.'/reporte.pdf';
        $this->reporte = '';
        $this->fecha_inicial = Carbon::now()->format('Y-m-d');
        $this->fecha_final = Carbon::now()->format('Y-m-d');
        $this->cliente = '';
        $this->reporte_generado = 'NO';
        $this->reporte_generado_2 = '';
        $data =
            [
                'reporte'=>$this->reporte,
                'fecha_inicial'=>$this->fecha_inicial,
                'fecha_final'=>$this->fecha_final,
                'cliente'=>$this->cliente,
                'reporte_generado'=>$this->reporte_generado,
                'reporte_generado_2'=>$this->reporte_generado_2,
            ];
        $this->form->fill($data);
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Seleccion de Reporte')
                ->visible(function (Get $get){return $get('reporte_generado') == 'NO';})
                ->schema([
                    Hidden::make('reporte_generado')
                    ->default('NO'),
                    Select::make('reporte')
                    ->default('')
                    ->options([
                        'ventasgral'=>'Ventas',
                        'facturas'=>'Facturas',
                        'cuentas'=>'Cuentas por Cobrar',
                        'pagar'=>'Cuentas por Pagar'
                    ])->reactive()

                ]),
                Fieldset::make('filtroventas')
                ->visible(function (Get $get){
                    return $get('reporte') == 'ventasgral';
                })
                ->label('Filtro de Ventas')
                ->schema([
                    DatePicker::make('fecha_inicial')->label('Fecha Inicial')->default(Carbon::now()->format('Y-m-d')),
                    DatePicker::make('fecha_final')->label('Fecha Final')->default(Carbon::now()->format('Y-m-d')),
                    Select::make('cliente')
                    ->searchable()
                    ->options(function (){
                        return Auxiliares::where('codigo','40101000')
                            ->distinct('concepto')
                            ->pluck('concepto');
                    }),
                    Actions::make([
                        Actions\Action::make('generar')
                        ->label('Generar Reporte')
                        ->action(function (Get $get,Set $set){
                            $reporte_url = 'storage/ventas_'.Filament::getTenant()->id.'.pdf';
                            if(\File::exists($reporte_url)) unlink($reporte_url);
                            $data = [
                                'team_id'=>Filament::getTenant()->id,
                                'fecha_inicial'=>$get('fecha_inicial'),
                                'fecha_final'=>$get('fecha_final'),
                                'cliente'=>$get('cliente')
                            ];
                            $html = View::make('ReportesAdmin.Ventas',$data)->render();
                            Browsershot::html($html)
                                ->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                ->noSandbox()
                                ->scale(0.8)->savePdf($reporte_url);
                            $pdfContent = file_get_contents($reporte_url);
                            $this->ReportePDF = base64_encode($pdfContent);
                            //dd($this->ReportePDF);
                            $this->reporte_url = 'ventas_'.Filament::getTenant()->id.'.pdf';
                            $set('reporte_generado','SI');
                            $set('reporte','');
                            $set('reporte_generado_2',$this->reporte_url);
                            //$set('ReportePDF',$this->reporte_url);
                        })
                    ])
                ]),
                Fieldset::make('filtrofacturas')
                    ->visible(function (Get $get){
                        return $get('reporte') == 'facturas';
                    })
                    ->label('Filtro de Fechas')
                    ->schema([
                        DatePicker::make('fecha_inicial')->label('Fecha Inicial')->default(Carbon::now()->format('Y-m-d')),
                        DatePicker::make('fecha_final')->label('Fecha Final')->default(Carbon::now()->format('Y-m-d')),
                        Actions::make([
                            Actions\Action::make('generar_1')
                                ->label('Generar Reporte')
                                ->action(function (Get $get,Set $set){
                                    $reporte_url = 'storage/ventas_'.Filament::getTenant()->id.'.pdf';
                                    if(\File::exists($reporte_url)) unlink($reporte_url);
                                    $data = [
                                        'team_id'=>Filament::getTenant()->id,
                                        'fecha_inicial'=>$get('fecha_inicial'),
                                        'fecha_final'=>$get('fecha_final'),
                                    ];
                                    $html = View::make('ReportesAdmin.Facturacion',$data)->render();
                                    Browsershot::html($html)
                                        ->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($reporte_url);
                                    $pdfContent = file_get_contents($reporte_url);
                                    $this->ReportePDF = base64_encode($pdfContent);
                                    //dd($this->ReportePDF);
                                    $this->reporte_url = 'facturas_'.Filament::getTenant()->id.'.pdf';
                                    $set('reporte_generado','SI');
                                    $set('reporte','');
                                    $set('reporte_generado_2',$this->reporte_url);
                                    //$set('ReportePDF',$this->reporte_url);
                                })
                        ])
                    ]),
                Fieldset::make('filtrocuentascobrar')
                    ->visible(function (Get $get){
                        return $get('reporte') == 'cuentas';
                    })
                    ->label('Filtro de Fechas')
                    ->schema([
                        DatePicker::make('fecha_inicial')->label('Fecha Inicial')->default(Carbon::now()->format('Y-m-d')),
                        DatePicker::make('fecha_final')->label('Fecha Final')->default(Carbon::now()->format('Y-m-d')),
                        Select::make('cliente')
                            ->searchable()
                            ->options(function (){
                                return Auxiliares::where('codigo','like','105%')
                                    ->distinct('cuenta')
                                    ->pluck('cuenta');
                            }),
                        Actions::make([
                            Actions\Action::make('generar_2')
                                ->label('Generar Reporte')
                                ->action(function (Get $get,Set $set){
                                    $reporte_url = 'storage/cuentas_'.Filament::getTenant()->id.'.pdf';
                                    if(\File::exists($reporte_url)) unlink($reporte_url);
                                    $data = [
                                        'team_id'=>Filament::getTenant()->id,
                                        'fecha_inicial'=>$get('fecha_inicial'),
                                        'fecha_final'=>$get('fecha_final'),
                                        'cliente'=>$get('cliente')
                                    ];
                                    $html = View::make('ReportesAdmin.CuentasCobrar',$data)->render();
                                    Browsershot::html($html)
                                        ->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($reporte_url);
                                    $pdfContent = file_get_contents($reporte_url);
                                    $this->ReportePDF = base64_encode($pdfContent);
                                    //dd($this->ReportePDF);
                                    $this->reporte_url = 'cuentas_'.Filament::getTenant()->id.'.pdf';
                                    $set('reporte_generado','SI');
                                    $set('reporte','');
                                    $set('reporte_generado_2',$this->reporte_url);
                                    //$set('ReportePDF',$this->reporte_url);
                                })
                        ])
                    ]),
                Fieldset::make('filtrocuentaspagar')
                    ->visible(function (Get $get){
                        return $get('reporte') == 'pagar';
                    })
                    ->label('Filtro de Fechas')
                    ->schema([
                        DatePicker::make('fecha_inicial')->label('Fecha Inicial')->default(Carbon::now()->format('Y-m-d')),
                        DatePicker::make('fecha_final')->label('Fecha Final')->default(Carbon::now()->format('Y-m-d')),
                        Select::make('cliente')
                            ->label('Proveedor')
                            ->searchable()
                            ->options(function (){
                                return Auxiliares::where('codigo','like','201%')
                                    ->distinct('cuenta')
                                    ->pluck('cuenta');
                            }),
                        Actions::make([
                            Actions\Action::make('generar_2')
                                ->label('Generar Reporte')
                                ->action(function (Get $get,Set $set){
                                    $reporte_url = 'storage/pagar_'.Filament::getTenant()->id.'.pdf';
                                    if(\File::exists($reporte_url)) unlink($reporte_url);
                                    $data = [
                                        'team_id'=>Filament::getTenant()->id,
                                        'fecha_inicial'=>$get('fecha_inicial'),
                                        'fecha_final'=>$get('fecha_final'),
                                        'cliente'=>$get('cliente')
                                    ];
                                    $html = View::make('ReportesAdmin.CuentasPagar',$data)->render();
                                    Browsershot::html($html)
                                        ->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($reporte_url);
                                    $pdfContent = file_get_contents($reporte_url);
                                    $this->ReportePDF = base64_encode($pdfContent);
                                    //dd($this->ReportePDF);
                                    $this->reporte_url = 'pagar_'.Filament::getTenant()->id.'.pdf';
                                    $set('reporte_generado','SI');
                                    $set('reporte','');
                                    $set('reporte_generado_2',$this->reporte_url);
                                    //$set('ReportePDF',$this->reporte_url);
                                })
                        ])
                    ]),
                Group::make([
                    Actions::make([
                        Actions\Action::make('Regresar')
                            ->label('Regresar')
                            ->color('danger')->badge()
                            ->action(function (Set $set){
                                $set('reporte_generado','NO');
                                $set('reporte','');
                                $set('reporte_generado_2',$this->reporte_url);
                                //$set('ReportePDF',$this->reporte_url);
                            })
                    ]),
                    \Filament\Forms\Components\View::make('ReportesAdmin.your-pdf-viewer')
                        ->columnSpanFull()
                        ->viewData(function () {
                            return ['pdfBase64' => $this->ReportePDF ?? 'No se ha cargado'] ;
                        })
                ])->visible(function (Get $get){return $get('reporte_generado') == 'SI';}),
            ]);
    }
}
