<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReportesNIFController;
use App\Models\CatPolizas;
use App\Models\Auxiliares;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Exportacion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Exportación de Datos';
    protected static ?string $navigationLabel = 'Exportación';
    protected static string $view = 'filament.pages.exportacion';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'periodo' => Filament::getTenant()->periodo,
            'ejercicio' => Filament::getTenant()->ejercicio,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Fieldset::make('Selección de Periodo')
                    ->schema([
                        Select::make('periodo')
                            ->label('Periodo (Mes)')
                            ->options([
                                1 => 'Enero',
                                2 => 'Febrero',
                                3 => 'Marzo',
                                4 => 'Abril',
                                5 => 'Mayo',
                                6 => 'Junio',
                                7 => 'Julio',
                                8 => 'Agosto',
                                9 => 'Septiembre',
                                10 => 'Octubre',
                                11 => 'Noviembre',
                                12 => 'Diciembre',
                            ])
                            ->default(Filament::getTenant()->periodo)
                            ->required()
                            ->live(),

                        Select::make('ejercicio')
                            ->label('Ejercicio (Año)')
                            ->options(function () {
                                $anioActual = date('Y');
                                $opciones = [];
                                for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                    $opciones[$i] = $i;
                                }
                                return $opciones;
                            })
                            ->default(Filament::getTenant()->ejercicio)
                            ->required()
                            ->live(),
                    ]),

                Fieldset::make('Exportar Datos Contables')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('exportar_polizas_auxiliares')
                                ->label('Pólizas con Auxiliares')
                                ->icon('heroicon-o-document-text')
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Exportar Pólizas con Auxiliares')
                                ->modalDescription('Se exportarán todas las pólizas del periodo seleccionado con sus auxiliares correspondientes.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $filename = $this->exportarPolizasAuxiliares($team_id, $periodo, $ejercicio);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Exportación completada')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('exportar_balanza_excel')
                                ->label('Balanza de Comprobación (Excel)')
                                ->icon('heroicon-o-table-cells')
                                ->color('info')
                                ->form([
                                    Select::make('nivel_detalle')
                                        ->label('Nivel de Detalle')
                                        ->options([
                                            'mayor' => 'Solo Cuentas de Mayor (Nivel 1)',
                                            'todas' => 'Todas las Cuentas (Todos los niveles)',
                                        ])
                                        ->default('mayor')
                                        ->required()
                                        ->helperText('Seleccione si desea ver solo las cuentas principales o todas las subcuentas'),
                                ])
                                ->modalHeading('Exportar Balanza de Comprobación')
                                ->modalDescription('Seleccione el nivel de detalle del reporte.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function (array $data) {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $nivel_detalle = $data['nivel_detalle'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'month' => $periodo,
                                            'year' => $ejercicio,
                                            'nivel_detalle' => $nivel_detalle
                                        ]);

                                        $filename = $controller->balanzaComprobacionExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Balanza de Comprobación generada')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('exportar_balanza_simplificada')
                                ->label('Balanza Simplificada (Excel)')
                                ->icon('heroicon-o-document-chart-bar')
                                ->color('success')
                                ->form([
                                    Select::make('nivel_detalle')
                                        ->label('Nivel de Detalle')
                                        ->options([
                                            'mayor' => 'Solo Cuentas de Mayor (Nivel 1)',
                                            'todas' => 'Todas las Cuentas (Todos los niveles)',
                                        ])
                                        ->default('mayor')
                                        ->required()
                                        ->helperText('Seleccione si desea ver solo las cuentas principales o todas las subcuentas'),
                                ])
                                ->modalHeading('Exportar Balanza Simplificada')
                                ->modalDescription('Exporta una balanza con solo: Saldo Inicial, Cargos, Abonos y Saldo Final.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function (array $data) {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $nivel_detalle = $data['nivel_detalle'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'month' => $periodo,
                                            'year' => $ejercicio,
                                            'nivel_detalle' => $nivel_detalle
                                        ]);

                                        $filename = $controller->balanzaSimplificadaExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Balanza Simplificada generada')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('exportar_todos_excel')
                                ->label('Estados Financieros NIF (Excel)')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Exportar Estados Financieros a Excel')
                                ->modalDescription('Se generará un archivo Excel con 5 hojas: Balanza, Balance General, Estado de Resultados, Cambios en Capital y Flujo de Efectivo.')
                                ->modalSubmitActionLabel('Exportar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $filename = $controller->exportarTodosExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Estados Financieros exportados')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->duration(5000)
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(3)
                    ]),

                Fieldset::make('Reportes Financieros')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('exportar_balance_general')
                                ->label('Balance General (Excel)')
                                ->icon('heroicon-o-scale')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Exportar Balance General')
                                ->modalDescription('Estado de Situación Financiera conforme a NIF B-6.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $filename = $controller->balanceGeneralExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Balance General exportado')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('exportar_estado_resultados')
                                ->label('Estado de Resultados (Excel)')
                                ->icon('heroicon-o-chart-bar')
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Exportar Estado de Resultados')
                                ->modalDescription('Estado de Resultados Integral conforme a NIF B-3.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $filename = $controller->estadoResultadosExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Estado de Resultados exportado')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('exportar_auxiliares')
                                ->label('Auxiliares (Excel)')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->color('warning')
                                ->form([
                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            Select::make('cuenta_inicio')
                                                ->label('Cuenta Inicial')
                                                ->searchable()
                                                ->options(function () {
                                                    $team_id = Filament::getTenant()->id;
                                                    return \Illuminate\Support\Facades\DB::table('cat_cuentas')
                                                        ->where('team_id', $team_id)
                                                        ->orderBy('codigo')
                                                        ->pluck(\Illuminate\Support\Facades\DB::raw("CONCAT(codigo, ' - ', nombre)"), 'codigo');
                                                })
                                                ->required()
                                                ->placeholder('Seleccione una cuenta'),

                                            Select::make('cuenta_fin')
                                                ->label('Cuenta Final')
                                                ->searchable()
                                                ->options(function () {
                                                    $team_id = Filament::getTenant()->id;
                                                    return \Illuminate\Support\Facades\DB::table('cat_cuentas')
                                                        ->where('team_id', $team_id)
                                                        ->orderBy('codigo')
                                                        ->pluck(\Illuminate\Support\Facades\DB::raw("CONCAT(codigo, ' - ', nombre)"), 'codigo');
                                                })
                                                ->required()
                                                ->placeholder('Seleccione una cuenta'),
                                        ]),

                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            Select::make('periodo_inicio')
                                                ->label('Periodo Inicial')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(1)
                                                ->required(),

                                            Select::make('ejercicio_inicio')
                                                ->label('Ejercicio Inicial')
                                                ->options(function () {
                                                    $anioActual = date('Y');
                                                    $opciones = [];
                                                    for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                                        $opciones[$i] = $i;
                                                    }
                                                    return $opciones;
                                                })
                                                ->default(Filament::getTenant()->ejercicio)
                                                ->required(),
                                        ]),

                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            Select::make('periodo_fin')
                                                ->label('Periodo Final')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(Filament::getTenant()->periodo)
                                                ->required(),

                                            Select::make('ejercicio_fin')
                                                ->label('Ejercicio Final')
                                                ->options(function () {
                                                    $anioActual = date('Y');
                                                    $opciones = [];
                                                    for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                                        $opciones[$i] = $i;
                                                    }
                                                    return $opciones;
                                                })
                                                ->default(Filament::getTenant()->ejercicio)
                                                ->required(),
                                        ]),
                                ])
                                ->modalHeading('Exportar Auxiliares')
                                ->modalDescription('Ingrese el rango de cuentas y el periodo para exportar los auxiliares.')
                                ->modalSubmitActionLabel('Exportar a Excel')
                                ->action(function (array $data) {
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'cuenta_inicio' => $data['cuenta_inicio'],
                                            'cuenta_fin' => $data['cuenta_fin'],
                                            'periodo_inicio' => $data['periodo_inicio'],
                                            'ejercicio_inicio' => $data['ejercicio_inicio'],
                                            'periodo_fin' => $data['periodo_fin'],
                                            'ejercicio_fin' => $data['ejercicio_fin'],
                                        ]);

                                        $filename = $controller->auxiliaresExcel($request);
                                        $url = asset('TMPCFDI/' . $filename);

                                        Notification::make()
                                            ->title('Auxiliares exportados')
                                            ->success()
                                            ->body('Descargando archivo Excel...')
                                            ->send();

                                        $this->js("
                                            const link = document.createElement('a');
                                            link.href = '{$url}';
                                            link.download = '{$filename}';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        ");

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al exportar')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(3)
                    ]),

                Fieldset::make('Información')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content(function () {
                                $empresa = Filament::getTenant()->name;
                                $periodo = $this->data['periodo'] ?? Filament::getTenant()->periodo;
                                $ejercicio = $this->data['ejercicio'] ?? Filament::getTenant()->ejercicio;

                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2'>
                                        <p class='text-sm'><strong>Empresa:</strong> {$empresa}</p>
                                        <p class='text-sm'><strong>Periodo seleccionado:</strong> {$periodo}/{$ejercicio}</p>
                                        <hr class='my-2'>
                                        <p class='text-xs text-gray-600 dark:text-gray-400'>
                                            <strong>Pólizas con Auxiliares:</strong> Exporta todas las pólizas del periodo con sus movimientos auxiliares.
                                            Cada póliza se muestra con su encabezado y debajo todos sus auxiliares (movimientos por cuenta).
                                        </p>
                                        <p class='text-xs text-gray-600 dark:text-gray-400 mt-2'>
                                            <strong>Balanza de Comprobación (Excel):</strong> Exporta la balanza de comprobación con saldo inicial, debe/haber y saldo final.
                                        </p>
                                        <p class='text-xs text-gray-600 dark:text-gray-400 mt-2'>
                                            <strong>Estados Financieros NIF (Excel):</strong> Exporta todos los estados financieros (Balanza, Balance General,
                                            Estado de Resultados, Cambios en Capital y Flujo de Efectivo) en un solo archivo con 5 hojas.
                                        </p>
                                        <p class='text-xs text-gray-500 dark:text-gray-500 mt-2'>
                                            Los archivos se guardan en: <code>public/TMPCFDI/</code>
                                        </p>
                                    </div>
                                ");
                            })
                    ])
            ]);
    }

    private function exportarPolizasAuxiliares($team_id, $periodo, $ejercicio)
    {
        // Obtener todas las pólizas del periodo
        $polizas = CatPolizas::where('team_id', $team_id)
            ->where('periodo', $periodo)
            ->where('ejercicio', $ejercicio)
            ->orderBy('folio')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pólizas y Auxiliares');

        // Encabezado del reporte
        $empresa = Filament::getTenant()->name;
        $sheet->setCellValue('A1', $empresa);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'PÓLIZAS CON AUXILIARES');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Periodo: ' . str_pad($periodo, 2, '0', STR_PAD_LEFT) . '/' . $ejercicio);
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;

        foreach ($polizas as $poliza) {
            // Encabezado de la póliza
            $sheet->setCellValue('A' . $row, 'PÓLIZA #' . $poliza->folio);
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE8F4F8');
            $row++;

            // Información de la póliza
            $sheet->setCellValue('A' . $row, 'Tipo:');
            $sheet->setCellValue('B' . $row, $poliza->tipo);
            $sheet->setCellValue('C' . $row, 'Fecha:');
            $sheet->setCellValue('D' . $row, $poliza->fecha);
            $sheet->setCellValue('E' . $row, 'Referencia:');
            $sheet->setCellValue('F' . $row, $poliza->referencia ?? '');
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $row++;

            $sheet->setCellValue('A' . $row, 'Concepto:');
            $sheet->setCellValue('B' . $row, $poliza->concepto);
            $sheet->mergeCells('B' . $row . ':H' . $row);
            $row++;

            $row++; // Espacio

            // Encabezados de auxiliares
            $headers = ['Cuenta', 'Nombre Cuenta', 'Cargo', 'Abono', 'Concepto', 'Factura'];
            $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

            foreach ($cols as $index => $col) {
                $sheet->setCellValue($col . $row, $headers[$index]);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E9F2');
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
            $row++;

            // Obtener auxiliares de esta póliza
            $auxiliares = Auxiliares::where('team_id', $team_id)
                ->where('cat_polizas_id', $poliza->id)
                ->orderBy('codigo')
                ->get();

            $total_cargo = 0;
            $total_abono = 0;

            foreach ($auxiliares as $auxiliar) {
                $sheet->setCellValue('A' . $row, $auxiliar->codigo);
                $sheet->setCellValue('B' . $row, $auxiliar->cuenta ?? '');
                $sheet->setCellValue('C' . $row, $auxiliar->cargo);
                $sheet->setCellValue('D' . $row, $auxiliar->abono);
                $sheet->setCellValue('E' . $row, $auxiliar->concepto ?? '');
                $sheet->setCellValue('F' . $row, $auxiliar->factura ?? '');

                // Formato de números
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                $total_cargo += $auxiliar->cargo;
                $total_abono += $auxiliar->abono;

                $row++;
            }

            // Totales de la póliza
            $sheet->setCellValue('A' . $row, 'TOTALES:');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->setCellValue('C' . $row, $total_cargo);
            $sheet->setCellValue('D' . $row, $total_abono);
            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFF2CC');

            $row += 3; // Espacio entre pólizas
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15); // Cuenta
        $sheet->getColumnDimension('B')->setWidth(35); // Nombre Cuenta
        $sheet->getColumnDimension('C')->setWidth(15); // Cargo
        $sheet->getColumnDimension('D')->setWidth(15); // Abono
        $sheet->getColumnDimension('E')->setWidth(40); // Concepto
        $sheet->getColumnDimension('F')->setWidth(20); // Factura

        $filename = 'Polizas_Auxiliares_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }
}
