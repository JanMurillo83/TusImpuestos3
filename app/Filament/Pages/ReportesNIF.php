<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReportesNIFController;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Response;

class ReportesNIF extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes NIF';
    protected static ?string $navigationLabel = 'Reportes NIF (Normas)';
    protected static ?string $pluralLabel = 'Reportes conforme a NIF';
    protected static string $view = 'filament.pages.reportes-nif';
    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        // Inicializar con valores del tenant
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

                Fieldset::make('Libros Contables Obligatorios')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('balanza_comprobacion')
                                ->label('Balanza de Comprobación')
                                ->icon('heroicon-o-table-cells')
                                ->color('primary')
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

                                    Select::make('mostrar_cuentas')
                                        ->label('Mostrar Cuentas')
                                        ->options([
                                            'todas' => 'Todas las cuentas',
                                            'con_movimiento' => 'Solo con saldo o movimientos',
                                        ])
                                        ->default('con_movimiento')
                                        ->required()
                                        ->helperText('Filtre las cuentas que desea visualizar'),
                                ])
                                ->modalHeading('Generar Balanza de Comprobación')
                                ->modalDescription('Seleccione el nivel de detalle del reporte.')
                                ->modalSubmitActionLabel('Generar PDF')
                                ->action(function (array $data) {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $nivel_detalle = $data['nivel_detalle'];
                                    $mostrar_cuentas = $data['mostrar_cuentas'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'month' => $periodo,
                                            'year' => $ejercicio,
                                            'nivel_detalle' => $nivel_detalle,
                                            'mostrar_cuentas' => $mostrar_cuentas
                                        ]);

                                        $pdf_url = $controller->balanzaComprobacion($request);
                                        $url = asset('TMPCFDI/BalanzaComprobacion_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Balanza de Comprobación generada')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('balanza_simplificada')
                                ->label('Balanza Simplificada')
                                ->icon('heroicon-o-document-text')
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

                                    Select::make('mostrar_cuentas')
                                        ->label('Mostrar Cuentas')
                                        ->options([
                                            'todas' => 'Todas las cuentas',
                                            'con_movimiento' => 'Solo con saldo o movimientos',
                                        ])
                                        ->default('con_movimiento')
                                        ->required()
                                        ->helperText('Filtre las cuentas que desea visualizar'),
                                ])
                                ->modalHeading('Generar Balanza Simplificada')
                                ->modalDescription('Formato simplificado con saldos en una sola columna (con signo).')
                                ->modalSubmitActionLabel('Generar PDF')
                                ->action(function (array $data) {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $nivel_detalle = $data['nivel_detalle'];
                                    $mostrar_cuentas = $data['mostrar_cuentas'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'month' => $periodo,
                                            'year' => $ejercicio,
                                            'nivel_detalle' => $nivel_detalle,
                                            'mostrar_cuentas' => $mostrar_cuentas
                                        ]);

                                        $pdf_url = $controller->balanzaSimplificada($request);
                                        $url = asset('TMPCFDI/BalanzaSimplificada_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Balanza Simplificada generada')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('libro_mayor')
                                ->label('Libro Mayor')
                                ->icon('heroicon-o-book-open')
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
                                ->modalHeading('Generar Libro Mayor')
                                ->modalDescription('Ingrese el rango de cuentas y el periodo para generar el libro mayor.')
                                ->modalSubmitActionLabel('Generar')
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

                                        $pdf_url = $controller->libroMayor($request);
                                        $url = asset('TMPCFDI/LibroMayor_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Libro Mayor generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('diario_general')
                                ->label('Diario General')
                                ->icon('heroicon-o-book-open')
                                ->color(Color::Stone)
                                ->form([
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
                                ->modalHeading('Generar Diario General')
                                ->modalDescription('Libro contable obligatorio con todas las pólizas cronológicamente.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function (array $data) {
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'periodo_inicio' => $data['periodo_inicio'],
                                            'ejercicio_inicio' => $data['ejercicio_inicio'],
                                            'periodo_fin' => $data['periodo_fin'],
                                            'ejercicio_fin' => $data['ejercicio_fin'],
                                        ]);

                                        $pdf_url = $controller->diarioGeneral($request);
                                        $url = asset('TMPCFDI/DiarioGeneral_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Diario General generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('polizas_descuadradas')
                                ->label('Pólizas Descuadradas')
                                ->icon('heroicon-o-exclamation-triangle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Detectar Pólizas Descuadradas')
                                ->modalDescription('Reporte de control de calidad para detectar pólizas con errores.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->polizasDescuadradas($request);
                                        $url = asset('TMPCFDI/PólizasDescuadradas_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Reporte generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('auxiliares_reporte')
                                ->label('Reporte de Auxiliares')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->color(Color::Yellow)
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
                                ->modalHeading('Generar Reporte de Auxiliares')
                                ->modalDescription('Ingrese el rango de cuentas y el periodo para generar el reporte de auxiliares.')
                                ->modalSubmitActionLabel('Generar')
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

                                        $pdf_url = $controller->auxiliaresReporte($request);
                                        $url = asset('TMPCFDI/Auxiliares_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Reporte de Auxiliares generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(5)
                    ]),

                Fieldset::make('Estados Financieros NIF')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('balance_general_nif')
                                ->label('Balance General (NIF B-6)')
                                ->icon('heroicon-o-scale')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Generar Balance General')
                                ->modalDescription('Estado de Situación Financiera conforme a NIF B-6. Incluye comparativo con ejercicio anterior.')
                                ->modalSubmitActionLabel('Generar Reporte')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        // Actualizar saldos antes de generar
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);

                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->balanceGeneralNIF($request);
                                        $ruta = public_path('TMPCFDI/BalanceGeneralNIF_' . $team_id . '.pdf');

                                        $url = asset('TMPCFDI/BalanceGeneralNIF_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Balance General generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('estado_resultados_nif')
                                ->label('Estado de Resultados (NIF B-3)')
                                ->icon('heroicon-o-chart-bar')
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Generar Estado de Resultados Integral')
                                ->modalDescription('Conforme a NIF B-3. Incluye utilidad bruta, de operación y neta con márgenes porcentuales.')
                                ->modalSubmitActionLabel('Generar Reporte')
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

                                        $pdf_url = $controller->estadoResultadosNIF($request);
                                        $ruta = public_path('TMPCFDI/EstadoResultadosNIF_' . $team_id . '.pdf');

                                        $url = asset('TMPCFDI/EstadoResultadosNIF_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Estado de Resultados generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('cambios_capital_nif')
                                ->label('Estado de Cambios en Capital (NIF B-4)')
                                ->icon('heroicon-o-arrow-trending-up')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalHeading('Generar Estado de Cambios en Capital Contable')
                                ->modalDescription('Conforme a NIF B-4. Muestra movimientos del capital durante el periodo.')
                                ->modalSubmitActionLabel('Generar Reporte')
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

                                        $pdf_url = $controller->estadoCambiosCapitalNIF($request);
                                        $ruta = public_path('TMPCFDI/EstadoCambiosCapitalNIF_' . $team_id . '.pdf');

                                        $url = asset('TMPCFDI/EstadoCambiosCapitalNIF_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Estado de Cambios en Capital generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('flujo_efectivo_nif')
                                ->label('Estado de Flujos de Efectivo (NIF B-2)')
                                ->icon('heroicon-o-banknotes')
                                ->color(Color::Slate)
                                ->requiresConfirmation()
                                ->modalHeading('Generar Estado de Flujos de Efectivo')
                                ->modalDescription('Conforme a NIF B-2. Método indirecto con actividades de operación, inversión y financiamiento.')
                                ->modalSubmitActionLabel('Generar Reporte')
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

                                        $pdf_url = $controller->estadoFlujoEfectivoNIF($request);
                                        $ruta = public_path('TMPCFDI/EstadoFlujoEfectivoNIF_' . $team_id . '.pdf');

                                        $url = asset('TMPCFDI/EstadoFlujoEfectivoNIF_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Estado de Flujos de Efectivo generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(4)
                    ]),

                Fieldset::make('Reportes Comparativos y de Análisis')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('balance_comparativo')
                                ->label('Balance Comparativo')
                                ->icon('heroicon-o-arrows-right-left')
                                ->color(Color::Indigo)
                                ->form([
                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            Select::make('periodo1')
                                                ->label('Periodo 1')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(Filament::getTenant()->periodo)
                                                ->required(),

                                            Select::make('ejercicio1')
                                                ->label('Ejercicio 1')
                                                ->options(function () {
                                                    $anioActual = date('Y');
                                                    $opciones = [];
                                                    for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                                        $opciones[$i] = $i;
                                                    }
                                                    return $opciones;
                                                })
                                                ->default(Filament::getTenant()->ejercicio - 1)
                                                ->required(),

                                            Select::make('periodo2')
                                                ->label('Periodo 2')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(Filament::getTenant()->periodo)
                                                ->required(),

                                            Select::make('ejercicio2')
                                                ->label('Ejercicio 2')
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
                                ->modalHeading('Balance General Comparativo')
                                ->modalDescription('Seleccione los dos periodos a comparar.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function (array $data) {
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'periodo1' => $data['periodo1'],
                                            'ejercicio1' => $data['ejercicio1'],
                                            'periodo2' => $data['periodo2'],
                                            'ejercicio2' => $data['ejercicio2'],
                                        ]);

                                        $pdf_url = $controller->balanceGeneralComparativo($request);
                                        $url = asset('TMPCFDI/BalanceComparativo_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Balance Comparativo generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('estado_resultados_comparativo')
                                ->label('Estado de Resultados Comparativo')
                                ->icon('heroicon-o-arrows-right-left')
                                ->color(Color::Sky)
                                ->form([
                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            Select::make('periodo1')
                                                ->label('Periodo 1')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(Filament::getTenant()->periodo)
                                                ->required(),

                                            Select::make('ejercicio1')
                                                ->label('Ejercicio 1')
                                                ->options(function () {
                                                    $anioActual = date('Y');
                                                    $opciones = [];
                                                    for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                                        $opciones[$i] = $i;
                                                    }
                                                    return $opciones;
                                                })
                                                ->default(Filament::getTenant()->ejercicio - 1)
                                                ->required(),

                                            Select::make('periodo2')
                                                ->label('Periodo 2')
                                                ->options([
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                                ])
                                                ->default(Filament::getTenant()->periodo)
                                                ->required(),

                                            Select::make('ejercicio2')
                                                ->label('Ejercicio 2')
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
                                ->modalHeading('Estado de Resultados Comparativo')
                                ->modalDescription('Compare ingresos y gastos entre dos periodos.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function (array $data) {
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'periodo1' => $data['periodo1'],
                                            'ejercicio1' => $data['ejercicio1'],
                                            'periodo2' => $data['periodo2'],
                                            'ejercicio2' => $data['ejercicio2'],
                                        ]);

                                        $pdf_url = $controller->estadoResultadosComparativo($request);
                                        $url = asset('TMPCFDI/EstadoResultadosComparativo_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Estado de Resultados Comparativo generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('antiguedad_saldos')
                                ->label('Antigüedad de Saldos')
                                ->icon('heroicon-o-clock')
                                ->color(Color::Amber)
                                ->form([
                                    Select::make('tipo')
                                        ->label('Tipo de Reporte')
                                        ->options([
                                            'cobrar' => 'Cuentas por Cobrar',
                                            'pagar' => 'Cuentas por Pagar',
                                        ])
                                        ->default('cobrar')
                                        ->required()
                                ])
                                ->modalHeading('Análisis de Antigüedad de Saldos')
                                ->modalDescription('Analiza la antigüedad de cuentas por cobrar o pagar.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function (array $data) {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge([
                                            'month' => $periodo,
                                            'year' => $ejercicio,
                                            'tipo' => $data['tipo']
                                        ]);

                                        $pdf_url = $controller->antiguedadSaldos($request);
                                        $tipo_nombre = $data['tipo'] == 'cobrar' ? 'CuentasPorCobrar' : 'CuentasPorPagar';
                                        $url = asset('TMPCFDI/AntiguedadSaldos_' . $tipo_nombre . '_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Antigüedad de Saldos generada')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('razones_financieras')
                                ->label('Razones Financieras')
                                ->icon('heroicon-o-calculator')
                                ->color(Color::Indigo)
                                ->requiresConfirmation()
                                ->modalHeading('Análisis de Razones Financieras')
                                ->modalDescription('Se generará el análisis de razones financieras del periodo seleccionado.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->razonesFinancieras($request);
                                        $url = asset('TMPCFDI/RazonesFinancieras_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Razones Financieras generadas')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(4)
                    ]),

                Fieldset::make('Reportes Fiscales (SAT)')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('reporte_iva')
                                ->label('Declaración de IVA')
                                ->icon('heroicon-o-currency-dollar')
                                ->color(Color::Stone)
                                ->requiresConfirmation()
                                ->modalHeading('Declaración Mensual de IVA')
                                ->modalDescription('Reporte obligatorio para declaración ante el SAT.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->reporteIVA($request);
                                        $url = asset('TMPCFDI/ReporteIVA_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Declaración de IVA generada')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('reporte_diot')
                                ->label('DIOT')
                                ->icon('heroicon-o-document-text')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('DIOT - Declaración Informativa de Operaciones con Terceros')
                                ->modalDescription('Reporte obligatorio mensual de operaciones con proveedores.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->reporteDIOT($request);
                                        $url = asset('TMPCFDI/DIOT_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('DIOT generada')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('reporte_retenciones')
                                ->label('Retenciones ISR/IVA')
                                ->icon('heroicon-o-receipt-percent')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalHeading('Reporte de Retenciones')
                                ->modalDescription('Detalle de retenciones de ISR e IVA realizadas y recibidas.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;

                                    try {
                                        $controller = new ReportesNIFController();
                                        $request = request();
                                        $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                        $pdf_url = $controller->reporteRetenciones($request);
                                        $url = asset('TMPCFDI/Retenciones_' . $team_id . '.pdf');

                                        Notification::make()
                                            ->title('Reporte de Retenciones generado')
                                            ->success()
                                            ->body('Abriendo vista previa...')
                                            ->send();

                                        $this->js("window.open('{$url}', '_blank')");
                                        return null;

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar reporte')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(3)
                    ]),

                Fieldset::make('Acciones Múltiples')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('generar_todos_nif')
                                ->label('Generar Todos')
                                ->icon('heroicon-o-document-duplicate')
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Generar Estados Financieros Completos')
                                ->modalDescription('Se generarán los 4 estados financieros conforme a NIF. Este proceso puede tardar unos segundos.')
                                ->modalSubmitActionLabel('Generar Todos')
                                ->action(function () {
                                    $state = $this->form->getState();
                                    $ejercicio = $state['ejercicio'];
                                    $periodo = $state['periodo'];
                                    $team_id = Filament::getTenant()->id;
                                    $errores = [];
                                    $archivos = [];

                                    // Actualizar saldos una sola vez
                                    try {
                                        (new ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al actualizar saldos')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                        return;
                                    }

                                    $controller = new ReportesNIFController();
                                    $request = request();
                                    $request->merge(['month' => $periodo, 'year' => $ejercicio]);

                                    // Generar cada reporte
                                    try {
                                        $controller->balanceGeneralNIF($request);
                                        $archivos[] = 'Balance General';
                                    } catch (\Exception $e) {
                                        $errores[] = 'Balance General: ' . $e->getMessage();
                                    }

                                    try {
                                        $controller->estadoResultadosNIF($request);
                                        $archivos[] = 'Estado de Resultados';
                                    } catch (\Exception $e) {
                                        $errores[] = 'Estado de Resultados: ' . $e->getMessage();
                                    }

                                    try {
                                        $controller->estadoCambiosCapitalNIF($request);
                                        $archivos[] = 'Cambios en Capital';
                                    } catch (\Exception $e) {
                                        $errores[] = 'Cambios en Capital: ' . $e->getMessage();
                                    }

                                    try {
                                        $controller->estadoFlujoEfectivoNIF($request);
                                        $archivos[] = 'Flujos de Efectivo';
                                    } catch (\Exception $e) {
                                        $errores[] = 'Flujos de Efectivo: ' . $e->getMessage();
                                    }

                                    if (empty($errores)) {
                                        Notification::make()
                                            ->title('Estados Financieros generados')
                                            ->success()
                                            ->body('Los 4 reportes han sido generados exitosamente en la carpeta public/TMPCFDI/')
                                            ->duration(5000)
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Generación con errores')
                                            ->warning()
                                            ->body('Generados: ' . implode(', ', $archivos) . '. Errores: ' . implode('; ', $errores))
                                            ->duration(8000)
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(1)
                    ]),

                Fieldset::make('Información')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('info_nif')
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
                                            Los reportes se generan conforme a las <strong>Normas de Información Financiera (NIF)</strong>
                                            vigentes en México para 2025.
                                        </p>
                                        <ul class='text-xs text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1'>
                                            <li><strong>NIF B-6:</strong> Estado de Situación Financiera (Balance General)</li>
                                            <li><strong>NIF B-3:</strong> Estado de Resultados Integral</li>
                                            <li><strong>NIF B-4:</strong> Estado de Cambios en el Capital Contable</li>
                                            <li><strong>NIF B-2:</strong> Estado de Flujos de Efectivo</li>
                                        </ul>
                                        <p class='text-xs text-gray-500 dark:text-gray-500 mt-2'>
                                            Los archivos PDF se guardan en: <code>public/TMPCFDI/</code>
                                        </p>
                                        <p class='text-xs text-amber-600 dark:text-amber-400 mt-2'>
                                            💡 <strong>Nota:</strong> Los PDFs se descargarán automáticamente al generarse.
                                        </p>
                                    </div>
                                ");
                            })
                    ])
            ]);
    }

    protected function getActions(): array
    {
        return [];
    }
}
