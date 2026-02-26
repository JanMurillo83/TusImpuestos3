<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use App\Models\AuxCFDI;
use App\Models\BancoCuentas;
use App\Models\ContaPeriodos;
use App\Models\Movbancos;
use App\Models\Saldosbanco;
use Asmit\ResizedColumn\HasResizableColumn;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action as ActionsAction;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListMovbancos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = MovbancosResource::class;
    public ?float $saldo_cuenta = 0;
    public ?float $saldo_cuenta_ant = 0;
    public ?float $saldo_cuenta_act = 0;
    public static ?array $selected_records = [];
    public ?int $recordid;

    public ?int $selected_tier;

    public function getExtraBodyAttributes(): array
    {
        return [
            'class' => 'movbancos-small-table',
        ];
    }

    /*protected function getTableHeading(): string
    {
        // Get the current tab from the request
        $currentUrl = request()->url();
        $urlParts = explode('/', $currentUrl);
        $tabSlug = end($urlParts);

        // If we have a valid tab slug
        if ($tabSlug && $tabSlug !== 'movbancos') {
            $cuentaId = null;
            $tiers = BancoCuentas::orderBy('id', 'asc')->get();

            foreach ($tiers as $tier) {
                $name = $tier->banco;
                $slug = str($name)->slug()->toString();

                if ($slug === $tabSlug) {
                    $cuentaId = $tier->id;
                    break;
                }
            }

            if ($cuentaId) {
                $ejercicio = Filament::getTenant()->ejercicio;
                $periodo = Filament::getTenant()->periodo;

                $saldo = Saldosbanco::where('cuenta', $cuentaId)
                    ->where('ejercicio', $ejercicio)
                    ->where('periodo', $periodo)
                    ->first();

                if ($saldo) {
                    $this->saldo_cuenta = $saldo->inicial;
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    $formattedBalance = $formatter->formatCurrency($saldo->inicial, 'MXN');

                    return "Movimientos Bancarios - Saldo Inicial: {$formattedBalance}";
                }
            }
        }

        return "Movimientos Bancarios";
    }*/

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Importar')
                ->color("primary")
                ->beforeUploadField([
                    Hidden::make('tax_id')
                        ->default(Filament::getTenant()->taxid),
                    Hidden::make('team_id')
                        ->default(Filament::getTenant()->id),
                    Hidden::make('ejercicio')
                        ->default(Filament::getTenant()->ejercicio),
                    Hidden::make('periodo')
                        ->default(Filament::getTenant()->periodo),
                    Hidden::make('contabilizada')
                        ->default('NO'),
                    Select::make('cuenta')
                        ->label('Cuenta Bancaria')
                        ->required()
                        ->options(BancoCuentas::where('team_id', Filament::getTenant()->id)->pluck('banco', 'id'))
                ])
                ->sampleExcel(
                    sampleData: [
                        ['dia' => '1', 'Tipo' => 'E', 'importe' => '1000.00', 'concepto' => 'Ejemplo Entrada', 'ejercicio' => 2024, 'periodo' => 1],
                        ['dia' => '31', 'Tipo' => 'S', 'importe' => '1000.00', 'concepto' => 'Ejemplo Salida', 'ejercicio' => 2024, 'periodo' => 1],
                    ],
                    fileName: 'ImportaMovBanco.xlsx',
                    sampleButtonLabel: 'Descargar Layout',
                    customiseActionUsing: fn(Action $action) => $action->color('gray')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                )->beforeImport(function (array $data, $livewire, $excelImportAction) {
                    $tax_id = $data['tax_id'];
                    $team_id = $data['team_id'];
                    $contabilizada = $data['contabilizada'];
                    $cuenta = $data['cuenta'];
                    //$pendiente = $data['importe'];
                    $excelImportAction->additionalData([
                        'tax_id' => $tax_id,
                        'team_id' => $team_id,
                        'contabilizada' => $contabilizada,
                        'cuenta' => $cuenta,

                    ]);
                })->afterImport(closure: function (array $data) {
                    $filen = $data['upload']->getFilename();
                    $fileName = storage_path('app/livewire-tmp/' . $filen);
                    $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileName);
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                    $reader->setReadEmptyCells(false);
                    $spreadsheet = $reader->load($fileName);
                    $spreadsheet = $spreadsheet->getActiveSheet();
                    $data_array = $spreadsheet->toArray();
                    $arrs = count($data_array);
                    for ($i = 0; $i < $arrs; $i++) {
                        if ($i > 0) {
                            $fecha = Carbon::create($data_array[$i][0])->format('Y-m-d');
                            $tipo = $data_array[$i][1];
                            $importe = floatval($data_array[$i][2]);
                            $peri = intval($data_array[$i][5]);
                            $ejer = intval($data_array[$i][4]);
                            //dd($tipo,$importe,$peri,$ejer);
                            //------------------------------------------------------
                            $val_sdos = DB::table('saldosbancos')
                                ->where('cuenta', $data['cuenta'])
                                ->where('ejercicio', $ejer)->get();
                            if (count($val_sdos) == 0) {
                                for ($i = 1; $i < 13; $i++) {
                                    DB::table('saldosbancos')->insert([
                                        'cuenta' => $data['cuenta'],
                                        'inicial' => 0.00,
                                        'ingresos' => 0.00,
                                        'egresos' => 0.00,
                                        'actual' => 0.00,
                                        'ejercicio' => $ejer,
                                        'periodo' => $i
                                    ]);
                                }
                            }
                            $sdos = DB::table('saldosbancos')
                                ->where('cuenta', $data['cuenta'])
                                ->where('ejercicio', $ejer)
                                ->where('periodo', $peri)->get();
                            //dd($sdos);
                            if (count($sdos) == 0) return;
                            $inicia = $sdos[0]->inicial;
                            $ingre = $sdos[0]->ingresos + $importe;
                            $salid = $sdos[0]->egresos + $importe;
                            if ($tipo == 'E') {
                                DB::table('saldosbancos')
                                    ->where('cuenta', $data['cuenta'])
                                    ->where('ejercicio', $ejer)
                                    ->where('periodo', $peri)->update([
                                        'ingresos' => $ingre
                                    ]);
                            } else {
                                DB::table('saldosbancos')
                                    ->where('cuenta', $data['cuenta'])
                                    ->where('ejercicio', $ejer)
                                    ->where('periodo', $peri)->update([
                                        'egresos' => $salid
                                    ]);
                            }
                            $sdos = DB::table('saldosbancos')
                                ->where('cuenta', $data['cuenta'])
                                ->where('ejercicio', $ejer)
                                ->where('periodo', $peri)->get();
                            $inicia = $sdos[0]->inicial;
                            $ingre = $sdos[0]->ingresos;
                            $salid = $sdos[0]->egresos;
                            $term = $inicia + $ingre - $salid;
                            DB::table('saldosbancos')
                                ->where('cuenta', $data['cuenta'])
                                ->where('ejercicio', $ejer)
                                ->where('periodo', $peri)->update([
                                    'actual' => $term
                                ]);
                            //$emp_a = Filament::getTenant()->id;
                            //DB::statement("DELETE FROM movbancos WHERE pendiente_apli = 0 AND contabilizada = 'NO' AND periodo = $peri AND ejercicio = $ejer AND team_id = $emp_a");
                            //------------------------------------------------------
                        }
                    }
                    //Notification::make()->title('Proceso Concluido')->success()->send();
                })->processCollectionUsing(function (string $modelClass, Collection $collection,$data) {

                    $ejercicio = Filament::getTenant()->ejercicio;
                    $periodo = Filament::getTenant()->periodo;
                    $tax_id = Filament::getTenant()->taxid;
                    $team_id = Filament::getTenant()->id;
                    $registros_creados = 0;

                    try {
                        foreach ($collection as $datos) {
                            // Validar que tenga las columnas requeridas
                            if (!isset($datos['dia']) || !isset($datos['tipo']) || !isset($datos['importe']) || !isset($datos['concepto'])) {
                                continue; // Salta registros incompletos
                            }

                            $fecha = Carbon::create($ejercicio, $periodo, intval($datos['dia']))->format('Y-m-d');

                            Movbancos::create([
                                'fecha' => $fecha,
                                'tax_id' => $tax_id,
                                'tipo' => $datos['tipo'],
                                'cuenta' => $data['cuenta'],
                                'importe' => floatval($datos['importe']),
                                'concepto' => $datos['concepto'],
                                'contabilizada' => 'NO',
                                'ejercicio' => $ejercicio,
                                'periodo' => $periodo,
                                'moneda' => 'MXN',
                                'tcambio' => 1.0,
                                'pendiente_apli' => floatval($datos['importe']),
                                'team_id' => $team_id,
                                'dia' => intval($datos['dia'])
                            ]);
                            $registros_creados++;
                        }

                        if ($registros_creados > 0) {
                            Notification::make()
                                ->title('Importación exitosa')
                                ->body("Se importaron {$registros_creados} movimientos bancarios.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin registros')
                                ->body('No se encontraron registros válidos para importar.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }

                    return $collection;
                })->visible(function(){
                    $team = Filament::getTenant()->id;
                    $periodo = Filament::getTenant()->periodo;
                    $ejercicio = Filament::getTenant()->ejercicio;
                    if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                    {
                        return true;
                    }
                    else{
                        $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                        if($estado == 1) return true;
                        else return false;
                    }
                }),
            Actions\CreateAction::make()
                ->label('Agregar')
                ->icon('fas-plus')
                ->createAnother(false)
                ->after(function ($record, $data) {
                    $id = $record->id;
                    $importe = $record->importe;
                    $dia = intval($data['fecha_dia']);
                    $fecha = Carbon::create(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, $dia);
                    Movbancos::where('id', $id)->update([
                        'fecha' => $fecha,
                        'pendiente_apli' => $importe
                    ]);
                })->visible(function(){
                    $team = Filament::getTenant()->id;
                    $periodo = Filament::getTenant()->periodo;
                    $ejercicio = Filament::getTenant()->ejercicio;
                    if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                    {
                        return true;
                    }
                    else{
                        $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                        if($estado == 1) return true;
                        else return false;
                    }
                }),
            \EightyNine\ExcelImport\ExcelImportAction::make('Importar2')
                ->label('Importar')
                ->color("primary")
                ->visible(false)
                ->beforeUploadField([
                    Hidden::make('tax_id')
                        ->default(Filament::getTenant()->taxid),
                    Hidden::make('team_id')
                        ->default(Filament::getTenant()->id),
                    Hidden::make('ejercicio')
                        ->default(Filament::getTenant()->ejercicio),
                    Hidden::make('periodo')
                        ->default(Filament::getTenant()->periodo),
                    Hidden::make('contabilizada')
                        ->default('NO'),
                    Select::make('cuenta')
                        ->label('Cuenta Bancaria')
                        ->required()
                        ->options(BancoCuentas::where('team_id', Filament::getTenant()->id)->pluck('banco', 'id'))
                ])
                ->sampleExcel(
                    sampleData: [
                        ['fecha' => '1', 'Tipo' => 'E', 'importe' => '1000.00', 'concepto' => 'Ejemplo Entrada', 'ejercicio' => 2024, 'periodo' => 1],
                        ['fecha' => '31', 'Tipo' => 'S', 'importe' => '1000.00', 'concepto' => 'Ejemplo Salida', 'ejercicio' => 2024, 'periodo' => 1],
                    ],
                    fileName: 'ImportaMovBanco.xlsx',
                    sampleButtonLabel: 'Descargar Layout',
                    customiseActionUsing: fn(Action $action) => $action->color('gray')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                )
                ->beforeImport(function (array $data, $livewire, $excelImportAction) {
                    $tax_id = $data['tax_id'];
                    $team_id = $data['team_id'];
                    $contabilizada = $data['contabilizada'];
                    $cuenta = $data['cuenta'];
                    //$pendiente = $data['importe'];
                    $excelImportAction->additionalData([
                        'tax_id' => $tax_id,
                        'team_id' => $team_id,
                        'contabilizada' => $contabilizada,
                        'cuenta' => $cuenta,

                    ]);
                })
                ->processCollectionUsing(function (string $modelClass, Collection $collection,$data) {
                    $tax_id = Filament::getTenant()->taxid;
                    $team_id = Filament::getTenant()->id;
                    $registros_creados = 0;

                    try {
                        foreach($collection as $datos) {
                            // Validar que tenga las columnas requeridas
                            if (!isset($datos['fecha']) || !isset($datos['tipo']) || !isset($datos['importe']) || !isset($datos['concepto'])) {
                                continue; // Salta registros incompletos
                            }

                            $fecha = Carbon::create($datos['fecha'])->format('Y-m-d');
                            $ejercicio = intval(Carbon::create($datos['fecha'])->format('Y'));
                            $periodo = intval(Carbon::create($datos['fecha'])->format('m'));

                            Movbancos::create([
                                'fecha' => $fecha,
                                'tax_id' => $tax_id,
                                'tipo' => $datos['tipo'],
                                'cuenta' => $data['cuenta'],
                                'importe' => floatval($datos['importe']),
                                'concepto' => $datos['concepto'],
                                'contabilizada' => 'NO',
                                'ejercicio' => $ejercicio,
                                'periodo' => $periodo,
                                'moneda' => 'MXN',
                                'tcambio' => 1.0,
                                'pendiente_apli' => floatval($datos['importe']),
                                'team_id' => $team_id,
                                'dia' => intval(Carbon::create($datos['fecha'])->format('d'))
                            ]);
                            $registros_creados++;
                        }

                        if ($registros_creados > 0) {
                            Notification::make()
                                ->title('Importación exitosa')
                                ->body("Se importaron {$registros_creados} movimientos bancarios.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin registros')
                                ->body('No se encontraron registros válidos para importar.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }

                                    return $collection;
                }),
            ActionsAction::make('exportarExcel')
                ->label('Exportar')
                ->icon('fas-file-excel')
                ->color('success')
                ->action(fn() => $this->exportarMovbancosExcel()),
        ];
    }

    public function exportarMovbancosExcel()
    {
        $query = Movbancos::query();
        $tenant = Filament::getTenant();
        if ($tenant) {
            $query->where('team_id', $tenant->id);
        }
        if (!empty($this->activeTab)) {
            $query->where('cuenta', $this->activeTab);
        }

        $movimientos = $query->orderBy('fecha')->orderBy('id')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Fecha',
            'Tipo',
            'Tercero',
            'Cuenta',
            'Factura',
            'Importe',
            'Moneda',
            'Concepto',
            'Contabilizada',
            'UUID',
            'Ejercicio',
            'Periodo',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $row = 2;
        foreach ($movimientos as $movimiento) {
            $cuenta = BancoCuentas::find($movimiento->cuenta);
            $tipo = $movimiento->tipo === 'E' ? 'Ingreso' : 'Egreso';

            $sheet->setCellValue('A' . $row, $movimiento->fecha?->format('Y-m-d'));
            $sheet->setCellValue('B' . $row, $tipo);
            $sheet->setCellValue('C' . $row, $movimiento->tercero);
            $sheet->setCellValue('D' . $row, $cuenta?->banco ?? $movimiento->cuenta);
            $sheet->setCellValue('E' . $row, $movimiento->factura);
            $sheet->setCellValue('F' . $row, $movimiento->importe);
            $sheet->setCellValue('G' . $row, $movimiento->moneda);
            $sheet->setCellValue('H' . $row, $movimiento->concepto);
            $sheet->setCellValue('I' . $row, $movimiento->contabilizada);
            $sheet->setCellValue('J' . $row, $movimiento->uuid);
            $sheet->setCellValue('K' . $row, $movimiento->ejercicio);
            $sheet->setCellValue('L' . $row, $movimiento->periodo);

            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'movimientos_bancarios_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        Notification::make()
            ->title('Movimientos exportados')
            ->success()
            ->send();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function getTabs(): array
    {
        //$tabs = ['all' => Tab::make('All')->badge($this->getModel()::count())];
        $tabs = [];
        $tiers = BancoCuentas::orderBy('id', 'asc')->get();

        foreach ($tiers as $tier) {
            $name = $tier->banco;
            $slug = str($name)->slug()->toString();

            $tabs[$tier->id] = Tab::make($name)
                ->modifyQueryUsing(function ($query) use ($tier) {
                    return $query->where('cuenta', $tier->id);
                });
            $this->selected_tier = $tier->id;
        }

        return $tabs;
    }
}
