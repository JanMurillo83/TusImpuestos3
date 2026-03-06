<?php

namespace App\Filament\Clusters\tiadmin\Resources\PagosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\PagosResource;
use App\Http\Controllers\TimbradoController;
use App\Models\CuentasCobrar;
use App\Models\Facturas;
use App\Models\Pagos;
use App\Models\ParPagos;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CreatePagos extends CreateRecord
{
    protected static string $resource = PagosResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $record;
        $factura = $record->getKey();
        $receptor = $data->cve_clie;
        $emisor = $data->dat_fiscal;

        if($data['tipo_compro'] == 'MUL')
            $res = app(TimbradoController::class)->TimbrarPagos($factura,$emisor,$receptor);
        else
            $res = app(TimbradoController::class)->TimbrarPagos_Uni($factura,$emisor,$receptor);

        $resultado = json_decode($res);
        $codigores = $resultado->codigo;

        if($codigores == "200")
        {
            $partidas_pagos = ParPagos::where('pagos_id',$factura)->get();
            foreach($partidas_pagos as $partida){
                $fact_pag = Facturas::where('id',$partida->uuidrel)->first();
                Facturas::where('id',$partida->uuidrel)->decrement('pendiente_pago', $partida->imppagado);
                CuentasCobrar::where('team_id',Filament::getTenant()->id)->where('concepto',1)->where('documento',$fact_pag->docto)->decrement('saldo',$partida->imppagado);
                CuentasCobrar::create([
                    'cliente'=>$record->cve_clie,
                    'concepto'=>9,
                    'descripcion'=>'Pago Factura',
                    'documento'=>$record->serie.$record->folio,
                    'fecha'=>Carbon::now(),
                    'vencimiento'=>Carbon::now(),
                    'importe'=>$partida->imppagado,
                    'saldo'=> 0,
                    'team_id'=>Filament::getTenant()->id,
                    'refer'=>$fact_pag->id
                ]);
            }
            $date = new \DateTime('now', new \DateTimeZone('America/Mexico_City'));
            $facturamodel = Pagos::where('id',$factura)->first();
            $facturamodel->timbrado = 'SI';
            $facturamodel->xml = $resultado->cfdi;
            $facturamodel->fecha_tim = $date;
            $facturamodel->save();
            $res2 = app(TimbradoController::class)->actualiza_pag_tim($factura,$resultado->cfdi,"P");
            $mensaje_graba = 'Comprobante Timbrado Se genero el CFDI UUID: '.$res2;

            PagosResource::makePrint($record);

            Notification::make()
                ->success()
                ->title('Pago Timbrado Correctamente')
                ->body($mensaje_graba)
                ->duration(2000)
                ->send();
        }
        else{
            $mensaje_graba = $resultado->mensaje;
            Notification::make()
                ->warning()
                ->title('Error al Timbrar el Documento')
                ->body($mensaje_graba)
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importar_partidas')
                ->label('Importar partidas')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Importar partidas desde Excel')
                ->modalDescription('Sube un Excel con encabezados. Puedes usar "factura_id" o "folio" (serie+folio).')
                ->modalWidth('lg')
                ->form([
                    Placeholder::make('layout_info')
                        ->label('Layout')
                        ->content('Descarga el layout y llena tus partidas. Columnas: factura_id, folio, moneda, saldoant, imppagado, equivalencia, parcialidad.'),
                    FileUpload::make('archivo')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->storeFiles(false)
                        ->hintAction(
                            FormAction::make('descargar_layout')
                                ->label('Descargar layout')
                                ->action(fn () => $this->downloadLayout())
                        ),
                    Toggle::make('reemplazar')
                        ->label('Reemplazar partidas actuales')
                        ->default(false),
                ])
                ->action(fn (array $data) => $this->importarPartidasDesdeExcel($data)),
        ];
    }

    protected function importarPartidasDesdeExcel(array $data): void
    {
        $state = $this->form->getState();
        $cveClie = $state['cve_clie'] ?? null;

        if (! $cveClie) {
            Notification::make()
                ->warning()
                ->title('Selecciona un cliente')
                ->body('Antes de importar, selecciona el cliente en el formulario.')
                ->send();
            return;
        }

        $file = $data['archivo'] ?? null;
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        $path = $this->resolveUploadedFilePath($file);
        if (! $path || ! is_file($path)) {
            Notification::make()
                ->danger()
                ->title('Archivo inválido')
                ->body('No se pudo leer el archivo. Intenta subirlo de nuevo.')
                ->send();
            return;
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            Notification::make()
                ->warning()
                ->title('Archivo vacío')
                ->body('El archivo no contiene datos para importar.')
                ->send();
            return;
        }

        $headers = array_map(
            fn ($value) => (string) Str::of($value ?? '')
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_'),
            $rows[0]
        );

        $dataRows = array_slice($rows, 1);
        $imported = [];
        $errors = [];

        $tcambio = $this->parseNumber($state['tcambio'] ?? 1) ?? 1.0;
        if ($tcambio == 0.0) {
            $tcambio = 1.0;
        }

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                continue;
            }

            $rowData = [];
            foreach ($headers as $i => $key) {
                if ($key !== '') {
                    $rowData[$key] = $row[$i] ?? null;
                }
            }

            $facturaId = $this->firstFilled($rowData, ['factura_id', 'id_factura', 'uuidrel', 'id']);
            $folio = $this->firstFilled($rowData, ['folio', 'factura']);

            if (! $facturaId && ! $folio) {
                $errors[] = "Fila {$rowNumber}: falta factura_id o folio.";
                continue;
            }

            $facturaQuery = Facturas::query()
                ->where('clie', $cveClie)
                ->where('estado', 'Timbrada')
                ->where('forma', 'PPD');

            $factura = null;
            if ($facturaId) {
                $factura = (clone $facturaQuery)->where('id', (int) $facturaId)->first();
            }

            if (! $factura && $folio) {
                $factura = (clone $facturaQuery)
                    ->whereRaw('CONCAT(serie,folio) = ?', [trim((string) $folio)])
                    ->first();
            }

            if (! $factura) {
                $errors[] = "Fila {$rowNumber}: factura no encontrada.";
                continue;
            }

            $saldoant = $this->parseNumber($this->firstFilled($rowData, ['saldoant', 'saldo_anterior']));
            if ($saldoant === null) {
                $saldoant = (float) $factura->pendiente_pago;
            }

            $imppagado = $this->parseNumber($this->firstFilled($rowData, ['imppagado', 'monto_pago', 'monto_del_pago']));
            if ($imppagado === null) {
                $imppagado = round($saldoant * $tcambio, 2);
            }

            $equivalencia = $this->parseNumber($this->firstFilled($rowData, ['equivalencia', 'tcambio']));
            if (! $equivalencia) {
                $equivalencia = $tcambio;
            }

            $parcialidad = (int) ($this->firstFilled($rowData, ['parcialidad']) ?? 1);

            $moneda = strtoupper(trim((string) ($this->firstFilled($rowData, ['moneda']) ?? $factura->moneda ?? 'MXN')));
            if (! in_array($moneda, ['MXN', 'USD', 'XXX'], true)) {
                $moneda = $factura->moneda ?? 'MXN';
            }

            $baseiva = round($imppagado / 1.16, 6);
            $montoiva = round(($imppagado / 1.16) * 0.16, 6);
            $insoluto = round(($saldoant * $tcambio) - $imppagado, 6);

            $imported[] = [
                'uuidrel' => $factura->id,
                'unitario' => $saldoant,
                'importe' => $saldoant,
                'moneda' => $moneda,
                'saldoant' => $saldoant,
                'imppagado' => $imppagado,
                'insoluto' => $insoluto,
                'equivalencia' => $equivalencia,
                'parcialidad' => $parcialidad ?: 1,
                'objeto' => '02',
                'tasaiva' => 0.16,
                'baseiva' => $baseiva,
                'montoiva' => $montoiva,
                'team_id' => Filament::getTenant()->id,
            ];
        }

        if (! $imported) {
            Notification::make()
                ->warning()
                ->title('Sin partidas importadas')
                ->body($this->formatImportErrors($errors))
                ->send();
            return;
        }

        $shouldReplace = (bool) ($data['reemplazar'] ?? false);
        $partidas = $shouldReplace ? [] : ($state['Partidas'] ?? []);
        $partidas = array_values($partidas);
        $this->form->fill(array_merge($state, [
            'Partidas' => array_merge($partidas, $imported),
        ]));

        Notification::make()
            ->success()
            ->title('Partidas importadas')
            ->body('Se importaron ' . count($imported) . ' partidas.')
            ->send();

        if ($errors) {
            Notification::make()
                ->warning()
                ->title('Algunas filas se omitieron')
                ->body($this->formatImportErrors($errors))
                ->send();
        }
    }

    protected function downloadLayout()
    {
        $headers = [
            'factura_id',
            'folio',
            'moneda',
            'saldoant',
            'imppagado',
            'equivalencia',
            'parcialidad',
        ];
        $example = [
            123,
            'A123',
            'MXN',
            1000.00,
            1000.00,
            1,
            1,
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($example, null, 'A2');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'layout_partidas_pagos.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    protected function resolveUploadedFilePath(mixed $file): ?string
    {
        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            return $file->getRealPath();
        }

        if (is_string($file)) {
            $localPath = storage_path('app/' . ltrim($file, '/'));
            return is_file($localPath) ? $localPath : null;
        }

        return null;
    }

    protected function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^\d\.\-]/', '', (string) $value);

        return $clean === '' ? null : (float) $clean;
    }

    protected function firstFilled(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    protected function formatImportErrors(array $errors): string
    {
        if (! $errors) {
            return 'No se encontraron filas válidas.';
        }

        $visible = array_slice($errors, 0, 5);
        $message = implode(' ', $visible);

        if (count($errors) > 5) {
            $message .= ' (+' . (count($errors) - 5) . ' mas)';
        }

        return $message;
    }
}
