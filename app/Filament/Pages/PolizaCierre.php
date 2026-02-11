<?php

namespace App\Filament\Pages;

use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
use App\Services\PolizaCierreService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions;
use Illuminate\Support\HtmlString;

class PolizaCierre extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $title = 'Periodo de Ajuste y Póliza de Cierre';
    protected static ?string $navigationLabel = 'Cierre Contable';
    protected static string $view = 'filament.pages.poliza-cierre';
    protected static ?int $navigationSort = 20;

    public ?array $data = [];
    protected $polizaCierreService;

    public function boot(PolizaCierreService $service): void
    {
        $this->polizaCierreService = $service;
    }

    public function mount(): void
    {
        $this->form->fill([
            'ejercicio' => Filament::getTenant()->ejercicio,
            'periodo' => 12,
            'cuenta_resultado' => '30401000',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Fieldset::make('Configuración General')
                    ->schema([
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
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->dispatch('refreshStatus');
                            }),

                        Select::make('periodo')
                            ->label('Periodo para Cierre')
                            ->options([
                                12 => 'Diciembre (Periodo 12)',
                                13 => 'Periodo de Ajuste (Periodo 13)',
                            ])
                            ->default(12)
                            ->required()
                            ->live()
                            ->helperText('Seleccione el periodo donde se generará la póliza de cierre'),

                        Select::make('cuenta_resultado')
                            ->label('Cuenta para Resultado del Ejercicio')
                            ->options(function () {
                                $team_id = Filament::getTenant()->id;
                                return CatCuentas::where('team_id', $team_id)
                                    ->where('tipo', 3) // Capital
                                    ->pluck('nombre', 'codigo')
                                    ->toArray();
                            })
                            ->default('30401000')
                            ->required()
                            ->searchable()
                            ->helperText('Cuenta donde se registrará la utilidad o pérdida del ejercicio'),
                    ])->columns(3),

                Fieldset::make('Periodo de Ajuste (Periodo 13)')
                    ->schema([
                        Placeholder::make('info_periodo_ajuste')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="text-sm space-y-2">
                                    <p>El <strong>Periodo de Ajuste (Periodo 13)</strong> es un periodo adicional después del cierre de diciembre,
                                    utilizado para registrar ajustes, correcciones y reclasificaciones que afectan el ejercicio que ya cerró.</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        • Se registran ajustes posteriores al cierre<br>
                                        • Se corrigen errores del ejercicio<br>
                                        • Se realizan reclasificaciones contables
                                    </p>
                                </div>
                            ')),

                        Actions::make([
                            Actions\Action::make('habilitar_periodo_ajuste')
                                ->label('Habilitar Periodo 13')
                                ->icon('heroicon-o-lock-open')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Habilitar Periodo de Ajuste')
                                ->modalDescription('Se habilitará el periodo 13 para registrar ajustes del ejercicio.')
                                ->modalSubmitActionLabel('Habilitar')
                                ->action(function () {
                                    try {
                                        $state = $this->form->getState();
                                        $team_id = Filament::getTenant()->id;
                                        $ejercicio = $state['ejercicio'];

                                        $this->polizaCierreService->habilitarPeriodoAjuste($team_id, $ejercicio);

                                        Notification::make()
                                            ->title('Periodo de Ajuste Habilitado')
                                            ->success()
                                            ->body("El periodo 13 (ajuste) para el ejercicio {$ejercicio} ha sido habilitado.")
                                            ->send();

                                        $this->dispatch('refreshStatus');

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('cerrar_periodo_ajuste')
                                ->label('Cerrar Periodo 13')
                                ->icon('heroicon-o-lock-closed')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Cerrar Periodo de Ajuste')
                                ->modalDescription('Se cerrará el periodo 13. No se podrán realizar más movimientos en este periodo.')
                                ->modalSubmitActionLabel('Cerrar')
                                ->action(function () {
                                    try {
                                        $state = $this->form->getState();
                                        $team_id = Filament::getTenant()->id;
                                        $ejercicio = $state['ejercicio'];

                                        $result = $this->polizaCierreService->cerrarPeriodoAjuste($team_id, $ejercicio);

                                        if ($result) {
                                            Notification::make()
                                                ->title('Periodo de Ajuste Cerrado')
                                                ->success()
                                                ->body("El periodo 13 (ajuste) para el ejercicio {$ejercicio} ha sido cerrado.")
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('No se encontró periodo de ajuste')
                                                ->warning()
                                                ->body("No existe un periodo 13 habilitado para cerrar.")
                                                ->send();
                                        }

                                        $this->dispatch('refreshStatus');

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(2),

                        Placeholder::make('estado_periodo_13')
                            ->label('Estado Actual del Periodo 13')
                            ->content(function () {
                                $state = $this->form->getState();
                                $team_id = Filament::getTenant()->id;
                                $ejercicio = $state['ejercicio'] ?? Filament::getTenant()->ejercicio;

                                $periodo = ContaPeriodos::where('team_id', $team_id)
                                    ->where('ejercicio', $ejercicio)
                                    ->where('periodo', 13)
                                    ->where('es_ajuste', true)
                                    ->first();

                                if (!$periodo) {
                                    return new HtmlString('<span class="text-sm font-bold text-gray-500">❌ No habilitado</span>');
                                }

                                $estado = $periodo->estado == 1 ? 'ABIERTO' : 'CERRADO';
                                $color = $periodo->estado == 1 ? 'green' : 'red';

                                return new HtmlString("<span class='text-sm font-bold text-{$color}-600'>✓ {$estado}</span>");
                            }),
                    ]),

                Fieldset::make('Póliza de Cierre del Ejercicio')
                    ->schema([
                        Placeholder::make('info_poliza_cierre')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="text-sm space-y-2">
                                    <p>La <strong>Póliza de Cierre</strong> cancela todas las cuentas de resultados (ingresos y egresos)
                                    trasladando el resultado neto (utilidad o pérdida) a la cuenta de capital correspondiente.</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        • Cancela cuentas de tipo 4 (Ingresos)<br>
                                        • Cancela cuentas de tipo 5 (Egresos)<br>
                                        • Traslada utilidad/pérdida a cuenta de capital
                                    </p>
                                </div>
                            ')),

                        Actions::make([
                            Actions\Action::make('generar_poliza_cierre')
                                ->label('Generar Póliza de Cierre')
                                ->icon('heroicon-o-document-check')
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Generar Póliza de Cierre')
                                ->modalDescription(fn () => 'Se generará la póliza de cierre del ejercicio ' . ($this->data['ejercicio'] ?? '') . ' en el periodo ' . ($this->data['periodo'] ?? '') . '. Esta póliza cancelará todas las cuentas de resultados.')
                                ->modalSubmitActionLabel('Generar')
                                ->action(function () {
                                    try {
                                        $state = $this->form->getState();
                                        $team_id = Filament::getTenant()->id;
                                        $ejercicio = $state['ejercicio'];
                                        $periodo = $state['periodo'];
                                        $cuentaResultado = $state['cuenta_resultado'];

                                        $poliza = $this->polizaCierreService->generarPolizaCierre(
                                            $team_id,
                                            $ejercicio,
                                            $periodo,
                                            $cuentaResultado
                                        );

                                        $resultado = $poliza->abonos - $poliza->cargos;
                                        $tipoResultado = $resultado >= 0 ? 'UTILIDAD' : 'PÉRDIDA';
                                        $montoResultado = '$' . number_format(abs($resultado), 2);

                                        Notification::make()
                                            ->title('Póliza de Cierre Generada')
                                            ->success()
                                            ->body("Se generó la póliza de cierre #{$poliza->folio}. {$tipoResultado} del ejercicio: {$montoResultado}")
                                            ->duration(10000)
                                            ->send();

                                        $this->dispatch('refreshStatus');

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al generar póliza de cierre')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),

                            Actions\Action::make('eliminar_poliza_cierre')
                                ->label('Eliminar Póliza de Cierre')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Eliminar Póliza de Cierre')
                                ->modalDescription('¿Está seguro de eliminar la póliza de cierre? Esta acción eliminará la póliza y todos sus auxiliares.')
                                ->modalSubmitActionLabel('Eliminar')
                                ->action(function () {
                                    try {
                                        $state = $this->form->getState();
                                        $team_id = Filament::getTenant()->id;
                                        $ejercicio = $state['ejercicio'];
                                        $periodo = $state['periodo'];

                                        $this->polizaCierreService->eliminarPolizaCierre($team_id, $ejercicio, $periodo);

                                        Notification::make()
                                            ->title('Póliza de Cierre Eliminada')
                                            ->success()
                                            ->body("La póliza de cierre del ejercicio {$ejercicio} periodo {$periodo} ha sido eliminada.")
                                            ->send();

                                        $this->dispatch('refreshStatus');

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull()->columns(2),

                        Placeholder::make('estado_poliza_cierre')
                            ->label('Estado de Póliza de Cierre')
                            ->content(function () {
                                $state = $this->form->getState();
                                $team_id = Filament::getTenant()->id;
                                $ejercicio = $state['ejercicio'] ?? Filament::getTenant()->ejercicio;
                                $periodo = $state['periodo'] ?? 12;

                                $poliza = CatPolizas::where('team_id', $team_id)
                                    ->where('ejercicio', $ejercicio)
                                    ->where('periodo', $periodo)
                                    ->where('es_cierre', true)
                                    ->first();

                                if (!$poliza) {
                                    return new HtmlString('<span class="text-sm font-bold text-gray-500">❌ No existe</span>');
                                }

                                $resultado = $poliza->abonos - $poliza->cargos;
                                $tipoResultado = $resultado >= 0 ? 'UTILIDAD' : 'PÉRDIDA';
                                $montoResultado = '$' . number_format(abs($resultado), 2);
                                $color = $resultado >= 0 ? 'green' : 'red';

                                return new HtmlString("
                                    <div class='space-y-1'>
                                        <p class='text-sm font-bold text-{$color}-600'>✓ Póliza #{$poliza->folio} generada</p>
                                        <p class='text-xs text-gray-600'>{$tipoResultado}: {$montoResultado}</p>
                                        <p class='text-xs text-gray-500'>Fecha: {$poliza->fecha->format('d/m/Y')}</p>
                                    </div>
                                ");
                            }),
                    ]),

                Fieldset::make('Información Importante')
                    ->schema([
                        Placeholder::make('instrucciones')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="text-sm space-y-2 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <p class="font-bold text-blue-800 dark:text-blue-300">📋 Proceso de Cierre Contable:</p>
                                    <ol class="list-decimal list-inside space-y-1 text-xs text-gray-700 dark:text-gray-300">
                                        <li>Revisar y cuadrar todas las pólizas del ejercicio</li>
                                        <li>Si es necesario, habilitar el Periodo 13 (ajuste) para correcciones</li>
                                        <li>Registrar los ajustes necesarios en el periodo 13</li>
                                        <li>Seleccionar el periodo donde se generará la póliza de cierre (12 o 13)</li>
                                        <li>Especificar la cuenta de capital donde se registrará el resultado</li>
                                        <li>Generar la póliza de cierre</li>
                                        <li>Revisar la póliza generada en el módulo de Pólizas</li>
                                        <li>Cerrar el periodo de ajuste (si se habilitó)</li>
                                    </ol>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">
                                        <strong>Nota:</strong> Una vez cerrado el periodo y generada la póliza de cierre,
                                        se recomienda no realizar más movimientos en el ejercicio cerrado.
                                    </p>
                                </div>
                            ')),
                    ]),
            ]);
    }
}
