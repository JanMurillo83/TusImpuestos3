<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use App\Models\Inventario;
use App\Models\ListaPrecio;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Colors\Color;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PreciosProductos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-tags';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $title = 'Precios de Productos';
    protected static ?string $navigationLabel = 'Precios';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.clusters.tiadmin.pages.precios-productos';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras', 'ventas', 'operador_comercial']);
    }

    public function table(Table $table): Table
    {
        $teamId = Filament::getTenant()->id;
        $labels = $this->priceLabels($teamId);

        return $table
            ->query(
                Inventario::query()
                    ->where('team_id', $teamId)
            )
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 'all'])
            ->striped()
            ->columns([
                TextColumn::make('clave')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('precio1')
                    ->label($labels[1])
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
                TextColumn::make('precio2')
                    ->label($labels[2])
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
                TextColumn::make('precio3')
                    ->label($labels[3])
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
                TextColumn::make('precio4')
                    ->label($labels[4])
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
                TextColumn::make('precio5')
                    ->label($labels[5])
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('importar_precios')
                    ->label('Importar Precios')
                    ->icon('fas-file-excel')
                    ->badge()
                    ->modalSubmitActionLabel('Importar')
                    ->modalCancelActionLabel('Cancelar')
                    ->form([
                        FormActions::make([
                            FormActions\Action::make('downloadLayoutPrecios')
                                ->label('Descargar Layout')
                                ->icon('fas-download')
                                ->color(Color::Blue)
                                ->action(fn () => static::downloadLayoutPrecios($teamId)),
                        ]),
                        FileUpload::make('ExcelFile')
                            ->label('Seleccionar Archivo')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data) use ($teamId) {
                        static::importarPrecios($teamId, $data);
                    }),
            ]);
    }

    private function priceLabels(int $teamId): array
    {
        $listas = ListaPrecio::where('team_id', $teamId)
            ->orderBy('lista')
            ->pluck('nombre', 'lista')
            ->all();

        return [
            1 => $listas[1] ?? 'Precio1',
            2 => $listas[2] ?? 'Precio2',
            3 => $listas[3] ?? 'Precio3',
            4 => $listas[4] ?? 'Precio4',
            5 => $listas[5] ?? 'Precio5',
        ];
    }

    public static function downloadLayoutPrecios(int $teamId)
    {
        $listas = ListaPrecio::where('team_id', $teamId)
            ->orderBy('lista')
            ->pluck('nombre', 'lista')
            ->all();
        $labels = [
            $listas[1] ?? 'Precio1',
            $listas[2] ?? 'Precio2',
            $listas[3] ?? 'Precio3',
            $listas[4] ?? 'Precio4',
            $listas[5] ?? 'Precio5',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_merge(['Clave', 'Descripcion'], $labels);
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $sheet->setCellValueByColumnAndRow(1, 2, 'SKU-001');
        $sheet->setCellValueByColumnAndRow(2, 2, 'Producto ejemplo');
        $sheet->setCellValueByColumnAndRow(3, 2, 100.00);
        $sheet->setCellValueByColumnAndRow(4, 2, 90.00);
        $sheet->setCellValueByColumnAndRow(5, 2, 80.00);
        $sheet->setCellValueByColumnAndRow(6, 2, 70.00);
        $sheet->setCellValueByColumnAndRow(7, 2, 60.00);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'layout_precios_productos.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public static function importarPrecios(int $teamId, array $data): void
    {
        $archivo = $data['ExcelFile']->path();
        $tipo = IOFactory::identify($archivo);
        $lector = IOFactory::createReader($tipo);
        $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
        $hoja = $libro->getActiveSheet();
        $rows = $hoja->toArray();

        $actualizados = 0;
        $noEncontrados = 0;
        $sinCambios = 0;

        for ($r = 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            $clave = trim((string) ($row[0] ?? ''));
            if ($clave === '') {
                continue;
            }

            $producto = Inventario::where('team_id', $teamId)
                ->where('clave', $clave)
                ->first();

            if (! $producto) {
                $noEncontrados++;
                continue;
            }

            $update = [];
            $valores = [
                'precio1' => $row[2] ?? null,
                'precio2' => $row[3] ?? null,
                'precio3' => $row[4] ?? null,
                'precio4' => $row[5] ?? null,
                'precio5' => $row[6] ?? null,
            ];

            foreach ($valores as $campo => $valor) {
                $parsed = static::parsePrice($valor);
                if ($parsed !== null) {
                    $update[$campo] = $parsed;
                }
            }

            if (empty($update)) {
                $sinCambios++;
                continue;
            }

            $producto->update($update);
            $actualizados++;
        }

        $mensaje = "Actualizados: {$actualizados}";
        if ($noEncontrados > 0) {
            $mensaje .= " | Claves no encontradas: {$noEncontrados}";
        }
        if ($sinCambios > 0) {
            $mensaje .= " | Sin cambios: {$sinCambios}";
        }

        Notification::make()
            ->title('Importación de precios completada')
            ->success()
            ->body($mensaje)
            ->send();
    }

    private static function parsePrice($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $clean = str_replace([',', '$'], '', $raw);
        return (float) $clean;
    }
}
