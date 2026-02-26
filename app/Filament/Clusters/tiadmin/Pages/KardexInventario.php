<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use App\Models\Conceptosmi;
use App\Models\Inventario;
use App\Models\Movinventario;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Browsershot\Browsershot;

class KardexInventario extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'fas-book';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $title = 'Consulta Kardex';
    protected static string $view = 'filament.clusters.tiadmin.pages.kardex-inventario';

    public array $data = [];
    public array $kardex = [
        'grupos' => [],
        'totales' => ['cant' => 0, 'costo' => 0, 'precio' => 0],
    ];

    public function mount(): void
    {
        $this->form->fill([
            'fecha_inicio' => Carbon::now()->startOfMonth()->toDateString(),
            'fecha_fin' => Carbon::now()->toDateString(),
            'producto_id' => null,
        ]);

        $this->cargarKardex();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros')
                    ->schema([
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha Inicio'),
                        DatePicker::make('fecha_fin')
                            ->label('Fecha Fin'),
                        Select::make('producto_id')
                            ->label('Producto')
                            ->options(Inventario::where('team_id', Filament::getTenant()->id)
                                ->selectRaw("id, CONCAT(clave,' - ',descripcion) as nombre")
                                ->pluck('nombre', 'id'))
                            ->searchable()
                            ->placeholder('Todos')
                            ->native(false),
                    ])->columns(3),
                Actions::make([
                    Action::make('consultar')
                        ->label('Consultar')
                        ->icon('fas-magnifying-glass')
                        ->action(fn () => $this->cargarKardex()),
                    Action::make('exportar_pdf')
                        ->label('Exportar PDF')
                        ->icon('fas-file-pdf')
                        ->color('danger')
                        ->action(fn () => $this->exportarPdf()),
                    Action::make('exportar_excel')
                        ->label('Exportar Excel')
                        ->icon('fas-file-excel')
                        ->color('success')
                        ->action(fn () => $this->exportarExcel()),
                ]),
                View::make('filament.clusters.tiadmin.pages.kardex-inventario-resultados')
                    ->columnSpanFull()
                    ->viewData(fn () => [
                        'kardex' => $this->kardex,
                        'fecha_inicio' => $this->form->getState()['fecha_inicio'] ?? null,
                        'fecha_fin' => $this->form->getState()['fecha_fin'] ?? null,
                    ]),
            ])
            ->statePath('data');
    }

    private function cargarKardex(): void
    {
        $this->kardex = $this->buildKardexData();
    }

    private function buildKardexData(): array
    {
        $state = $this->form->getState();
        $fechaInicio = $state['fecha_inicio'] ?? null;
        $fechaFin = $state['fecha_fin'] ?? null;
        $productoId = $state['producto_id'] ?? null;

        $query = Movinventario::where('team_id', Filament::getTenant()->id);

        if (!empty($fechaInicio)) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }
        if (!empty($productoId)) {
            $query->where('producto', $productoId);
        }

        $movimientos = $query->orderBy('fecha', 'desc')->get();

        if ($movimientos->isEmpty()) {
            return [
                'grupos' => [],
                'totales' => ['cant' => 0, 'costo' => 0, 'precio' => 0],
            ];
        }

        $productoIds = $movimientos->pluck('producto')->unique()->values();
        $conceptoIds = $movimientos->pluck('concepto')->filter()->unique()->values();

        $productos = Inventario::whereIn('id', $productoIds)->get()->keyBy('id');
        $conceptos = Conceptosmi::whereIn('id', $conceptoIds)->get()->keyBy('id');

        $grupos = $movimientos->groupBy('producto')->map(function ($items, $prodId) use ($productos, $conceptos) {
            $items = $items->sortByDesc('fecha')->values();

            $totalCant = $items->sum(fn ($m) => (float) $m->cant);
            $totalCosto = $items->sum(fn ($m) => (float) $m->cant * (float) $m->costo);
            $totalPrecio = $items->sum(fn ($m) => (float) $m->cant * (float) $m->precio);

            $producto = $productos->get($prodId);
            $ultimaFecha = $items->first()?->fecha;

            return [
                'producto_id' => $prodId,
                'producto_clave' => $producto?->clave ?? '',
                'producto_descripcion' => $producto?->descripcion ?? '',
                'ultima_fecha' => $ultimaFecha,
                'totales' => [
                    'cant' => $totalCant,
                    'costo' => $totalCosto,
                    'precio' => $totalPrecio,
                ],
                'movimientos' => $items->map(function ($m) use ($conceptos) {
                    $cant = (float) $m->cant;
                    $costo = (float) $m->costo;
                    $precio = (float) $m->precio;
                    return [
                        'fecha' => $m->fecha,
                        'tipo' => $m->tipo,
                        'concepto' => $conceptos->get($m->concepto)?->descripcion ?? '',
                        'cant' => $cant,
                        'costo' => $costo,
                        'precio' => $precio,
                        'importe_costo' => $cant * $costo,
                        'importe_precio' => $cant * $precio,
                    ];
                })->toArray(),
            ];
        })->values()->sortByDesc(fn ($g) => $g['ultima_fecha'])->values()->toArray();

        $granTotal = [
            'cant' => array_sum(array_map(fn ($g) => $g['totales']['cant'], $grupos)),
            'costo' => array_sum(array_map(fn ($g) => $g['totales']['costo'], $grupos)),
            'precio' => array_sum(array_map(fn ($g) => $g['totales']['precio'], $grupos)),
        ];

        return [
            'grupos' => $grupos,
            'totales' => $granTotal,
        ];
    }

    private function exportarPdf()
    {
        $data = $this->buildKardexData();
        $empresa = Filament::getTenant()->name;
        $fecha = Carbon::now()->format('d-m-Y');

        $ruta = public_path().'/TMPCFDI/KardexInventario_'.Filament::getTenant()->id.'.pdf';
        if (File::exists($ruta)) {
            File::delete($ruta);
        }

        $html = \Illuminate\Support\Facades\View::make('ReporteKardexInventario', [
            'empresa' => $empresa,
            'fecha' => $fecha,
            'kardex' => $data,
        ])->render();

        Browsershot::html($html)->format('Letter')
            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
            ->noSandbox()
            ->scale(0.8)
            ->savePdf($ruta);

        return response()->download($ruta);
    }

    private function exportarExcel()
    {
        $data = $this->buildKardexData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;
        foreach ($data['grupos'] as $grupo) {
            $productoNombre = trim(($grupo['producto_clave'] ?? '') . ' - ' . ($grupo['producto_descripcion'] ?? ''));
            $sheet->setCellValue('A' . $row, 'Producto: ' . $productoNombre);
            $row++;

            $headers = ['Fecha', 'Tipo', 'Concepto', 'Cantidad', 'Costo', 'Precio', 'Total Costo', 'Total Precio'];
            foreach ($headers as $idx => $header) {
                $sheet->setCellValueByColumnAndRow($idx + 1, $row, $header);
            }
            $row++;

            foreach ($grupo['movimientos'] as $mov) {
                $sheet->setCellValue('A' . $row, Carbon::parse($mov['fecha'])->format('Y-m-d'));
                $sheet->setCellValue('B' . $row, $mov['tipo']);
                $sheet->setCellValue('C' . $row, $mov['concepto']);
                $sheet->setCellValue('D' . $row, $mov['cant']);
                $sheet->setCellValue('E' . $row, $mov['costo']);
                $sheet->setCellValue('F' . $row, $mov['precio']);
                $sheet->setCellValue('G' . $row, $mov['importe_costo']);
                $sheet->setCellValue('H' . $row, $mov['importe_precio']);
                $row++;
            }

            $sheet->setCellValue('C' . $row, 'Totales:');
            $sheet->setCellValue('D' . $row, $grupo['totales']['cant']);
            $sheet->setCellValue('G' . $row, $grupo['totales']['costo']);
            $sheet->setCellValue('H' . $row, $grupo['totales']['precio']);
            $row += 2;
        }

        $sheet->setCellValue('C' . $row, 'Gran Total:');
        $sheet->setCellValue('D' . $row, $data['totales']['cant']);
        $sheet->setCellValue('G' . $row, $data['totales']['costo']);
        $sheet->setCellValue('H' . $row, $data['totales']['precio']);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'kardex_inventario_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        Notification::make()
            ->title('Kardex exportado')
            ->success()
            ->send();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
