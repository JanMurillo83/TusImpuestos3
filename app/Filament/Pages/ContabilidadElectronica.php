<?php

namespace App\Filament\Pages;

use App\Models\MainReportes;
use App\Services\BalanzaComprobacionXmlService;
use App\Services\CatalogoCuentasXmlService;
use App\Services\PolizasXmlService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ContabilidadElectronica extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'fas-file-code';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Contabilidad Electronica';
    protected static ?string $navigationLabel = 'Contabilidad Electronica';
    protected static string $view = 'filament.pages.contabilidad-electronica';

    private const REPORTES_XML = [
        'CatalogoCuentas_XML',
        'BalanzaComprobacion_XML',
        'PolizasPeriodo_XML',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        (new \App\Http\Controllers\ReportesController)->ContabilizaReporte(
            Filament::getTenant()->ejercicio,
            Filament::getTenant()->periodo,
            Filament::getTenant()->id
        );

        $this->form->fill([
            'reporte' => null,
            'periodo' => Filament::getTenant()->periodo,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Fieldset::make('Contabilidad Electronica')
                    ->schema([
                        Select::make('reporte')
                            ->label('Reporte')
                            ->options(fn () => MainReportes::whereIn('ruta', self::REPORTES_XML)
                                ->orderBy('reporte')
                                ->pluck('reporte', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('periodo')
                            ->label('Periodo')
                            ->options([
                                '1' => '1',
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',
                                '5' => '5',
                                '6' => '6',
                                '7' => '7',
                                '8' => '8',
                                '9' => '9',
                                '10' => '10',
                                '11' => '11',
                                '12' => '12',
                                '13' => '13 - Ajuste',
                            ])
                            ->default(Filament::getTenant()->periodo)
                            ->required(),
                    ])
                    ->columns(2),
                Actions::make([
                    Actions\Action::make('Descargar XML')
                        ->icon('fas-file-code')
                        ->color('primary')
                        ->disabled(fn (Get $get) => blank($get('reporte')))
                        ->action(function (Get $get) {
                            $reporteId = $get('reporte');
                            if (blank($reporteId)) {
                                Notification::make()
                                    ->title('Seleccione un reporte')
                                    ->warning()
                                    ->send();
                                return null;
                            }

                            $record = MainReportes::where('id', $reporteId)->first();
                            if (!$record) {
                                Notification::make()
                                    ->title('Reporte no encontrado')
                                    ->danger()
                                    ->send();
                                return null;
                            }

                            $ruta = $record->ruta;
                            $team_id = Filament::getTenant()->id;
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = $get('periodo') ?? Filament::getTenant()->periodo;

                            try {
                                if ($ruta === 'CatalogoCuentas_XML') {
                                    $service = new CatalogoCuentasXmlService();
                                    $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                    return response()->download($archivoGenerado);
                                }
                                if ($ruta === 'BalanzaComprobacion_XML') {
                                    (new \App\Http\Controllers\ReportesController)->ContabilizaReporte(
                                        $ejercicio,
                                        $periodo,
                                        $team_id
                                    );
                                    $service = new BalanzaComprobacionXmlService();
                                    $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                    return response()->download($archivoGenerado);
                                }
                                if ($ruta === 'PolizasPeriodo_XML') {
                                    $service = new PolizasXmlService();
                                    $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                    return response()->download($archivoGenerado);
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al generar XML')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }

                            return null;
                        }),
                ]),
            ]);
    }
}
