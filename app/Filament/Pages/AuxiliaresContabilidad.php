<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesNIFController;
use App\Models\Auxiliares;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class AuxiliaresContabilidad extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $navigationLabel = 'Auxiliares';
    protected static ?string $title = 'Reporte de Auxiliares';
    protected static string $view = 'filament.pages.auxiliares-contabilidad';
    protected static ?int $navigationSort = 6;

    public ?array $data = [];
    public array $cuentas = [];
    public array $totales = [
        'cargos' => 0,
        'abonos' => 0,
    ];

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        $this->form->fill([
            'periodo_inicio' => $tenant->periodo,
            'ejercicio_inicio' => $tenant->ejercicio,
            'periodo_fin' => $tenant->periodo,
            'ejercicio_fin' => $tenant->ejercicio,
        ]);

        $this->cargarVista();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('PDF')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->exportPdf()),
            Action::make('export_excel')
                ->label('Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->exportExcel()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Fieldset::make('Filtros')
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
                            ->placeholder('Opcional (toma la primera cuenta)')
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),

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
                            ->placeholder('Opcional (toma la última cuenta)')
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),

                        Select::make('periodo_inicio')
                            ->label('Periodo Inicial')
                            ->options([
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),

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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),

                        Select::make('periodo_fin')
                            ->label('Periodo Final')
                            ->options([
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),

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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->cargarVista()),
                    ])
                    ->columns(2),
            ]);
    }

    public function exportPdf(): void
    {
        $state = $this->form->getState();
        $team_id = Filament::getTenant()->id;

        try {
            $controller = new ReportesNIFController();
            $request = request();
            $request->merge([
                'cuenta_inicio' => $state['cuenta_inicio'] ?? null,
                'cuenta_fin' => $state['cuenta_fin'] ?? null,
                'periodo_inicio' => $state['periodo_inicio'],
                'ejercicio_inicio' => $state['ejercicio_inicio'],
                'periodo_fin' => $state['periodo_fin'],
                'ejercicio_fin' => $state['ejercicio_fin'],
            ]);

            $controller->auxiliaresReporte($request);
            $url = asset('TMPCFDI/Auxiliares_' . $team_id . '.pdf');

            Notification::make()
                ->title('Reporte de Auxiliares generado')
                ->success()
                ->body('Abriendo vista previa...')
                ->send();

            $this->js("window.open('{$url}', '_blank')");
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar reporte')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function exportExcel(): void
    {
        $state = $this->form->getState();

        try {
            $controller = new ReportesNIFController();
            $request = request();
            $request->merge([
                'cuenta_inicio' => $state['cuenta_inicio'] ?? null,
                'cuenta_fin' => $state['cuenta_fin'] ?? null,
                'periodo_inicio' => $state['periodo_inicio'],
                'ejercicio_inicio' => $state['ejercicio_inicio'],
                'periodo_fin' => $state['periodo_fin'],
                'ejercicio_fin' => $state['ejercicio_fin'],
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
    }

    public function cargarVista(): void
    {
        $state = $this->form->getState();
        $team = Filament::getTenant();

        $cuenta_inicio = $state['cuenta_inicio'] ?? null;
        $cuenta_fin = $state['cuenta_fin'] ?? null;
        $periodo_inicio = $state['periodo_inicio'] ?? $team->periodo;
        $ejercicio_inicio = $state['ejercicio_inicio'] ?? $team->ejercicio;
        $periodo_fin = $state['periodo_fin'] ?? $team->periodo;
        $ejercicio_fin = $state['ejercicio_fin'] ?? $team->ejercicio;

        [$cuenta_inicio, $cuenta_fin] = $this->obtenerRangoCuentas($team->id, $cuenta_inicio, $cuenta_fin);

        if (!$cuenta_inicio || !$cuenta_fin) {
            $this->cuentas = [];
            $this->totales = ['cargos' => 0, 'abonos' => 0];
            return;
        }

        $cuentas_query = DB::table('cat_cuentas')
            ->where('team_id', $team->id)
            ->where('codigo', '>=', $cuenta_inicio)
            ->where('codigo', '<=', $cuenta_fin)
            ->orderBy('codigo');

        $cuentas_data = [];
        $total_cargos = 0;
        $total_abonos = 0;

        foreach ($cuentas_query->get() as $cuenta) {
            $movimientos = $this->obtenerMovimientosCuentaPeriodo(
                $team->id,
                $cuenta->codigo,
                $ejercicio_inicio,
                $periodo_inicio,
                $ejercicio_fin,
                $periodo_fin
            );

            if ($movimientos->count() === 0) {
                continue;
            }

            $saldo_inicial = $this->calcularSaldoInicialCuenta(
                $team->id,
                $cuenta->codigo,
                $cuenta->naturaleza,
                $ejercicio_inicio,
                $periodo_inicio
            );

            $saldo_acumulado = $saldo_inicial;
            $movs = [];

            foreach ($movimientos as $mov) {
                $cargo = $mov->cargo ?? 0;
                $abono = $mov->abono ?? 0;

                $total_cargos += $cargo;
                $total_abonos += $abono;

                if (($cuenta->naturaleza ?? 'D') == 'A') {
                    $saldo_acumulado += ($abono - $cargo);
                } else {
                    $saldo_acumulado += ($cargo - $abono);
                }

                $fecha = $mov->fecha ? Carbon::parse($mov->fecha)->format('d-m-Y') : '';

                $movs[] = [
                    'fecha' => $fecha,
                    'folio' => trim(($mov->tipo ?? '') . '-' . ($mov->folio ?? ''), '-'),
                    'referencia' => $mov->factura ?? '',
                    'concepto' => $mov->concepto ?? '',
                    'cargo' => $cargo,
                    'abono' => $abono,
                    'saldo' => $saldo_acumulado,
                ];
            }

            $cuentas_data[] = [
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'naturaleza' => $cuenta->naturaleza,
                'saldo_inicial' => $saldo_inicial,
                'saldo_final' => $saldo_acumulado,
                'movimientos' => $movs,
            ];
        }

        $this->cuentas = $cuentas_data;
        $this->totales = [
            'cargos' => $total_cargos,
            'abonos' => $total_abonos,
        ];
    }

    private function obtenerMovimientosCuentaPeriodo($team_id, $codigo, $ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin)
    {
        return Auxiliares::join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->where('auxiliares.team_id', $team_id)
            ->where('auxiliares.codigo', $codigo)
            ->where(function ($query) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                if ($ejercicio_inicio == $ejercicio_fin) {
                    $query->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                        ->where('auxiliares.a_periodo', '>=', $periodo_inicio)
                        ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                } else {
                    $query->where(function ($q) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                        $q->where(function ($subq) use ($ejercicio_inicio, $periodo_inicio) {
                            $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                                ->where('auxiliares.a_periodo', '>=', $periodo_inicio);
                        })
                        ->orWhere(function ($subq) use ($ejercicio_inicio, $ejercicio_fin) {
                            $subq->where('auxiliares.a_ejercicio', '>', $ejercicio_inicio)
                                ->where('auxiliares.a_ejercicio', '<', $ejercicio_fin);
                        })
                        ->orWhere(function ($subq) use ($ejercicio_fin, $periodo_fin) {
                            $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_fin)
                                ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                        });
                    });
                }
            })
            ->select(
                'cat_polizas.fecha',
                'cat_polizas.folio',
                'cat_polizas.tipo',
                'auxiliares.factura',
                'auxiliares.concepto',
                'auxiliares.cargo',
                'auxiliares.abono'
            )
            ->orderBy('auxiliares.a_ejercicio')
            ->orderBy('auxiliares.a_periodo')
            ->orderBy('cat_polizas.fecha')
            ->orderBy('cat_polizas.folio')
            ->get();
    }

    private function calcularSaldoInicialCuenta($team_id, $codigo, $naturaleza, $ejercicio_inicio, $periodo_inicio)
    {
        $result = Auxiliares::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where(function ($query) use ($ejercicio_inicio, $periodo_inicio) {
                $query->where('a_ejercicio', '<', $ejercicio_inicio)
                    ->orWhere(function ($q) use ($ejercicio_inicio, $periodo_inicio) {
                        $q->where('a_ejercicio', '=', $ejercicio_inicio)
                            ->where('a_periodo', '<', $periodo_inicio);
                    });
            })
            ->selectRaw('SUM(cargo) as total_cargo, SUM(abono) as total_abono')
            ->first();

        $total_cargo = $result->total_cargo ?? 0;
        $total_abono = $result->total_abono ?? 0;

        if ($naturaleza == 'A') {
            return $total_abono - $total_cargo;
        }

        return $total_cargo - $total_abono;
    }

    private function obtenerRangoCuentas($team_id, $cuenta_inicio = null, $cuenta_fin = null)
    {
        $min_codigo = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->orderBy('codigo')
            ->value('codigo');

        $max_codigo = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->orderBy('codigo', 'desc')
            ->value('codigo');

        $inicio = $cuenta_inicio ?: $min_codigo;
        $fin = $cuenta_fin ?: $max_codigo;

        if ($inicio && $fin && strcmp($inicio, $fin) > 0) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        return [$inicio, $fin];
    }
}
