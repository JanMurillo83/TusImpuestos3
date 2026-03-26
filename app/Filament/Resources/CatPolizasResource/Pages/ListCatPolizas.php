<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\TableSettings;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ListCatPolizas extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = CatPolizasResource::class;

    protected function persistColumnWidthsToDatabase(): void
    {
        // Your custom database save logic here
        TableSettings::updateOrCreate(
            [
                'user_id' => $this->getUserId(),
                'resource' => $this->getResourceModelFullPath(), // e.g., 'App\Models\User'
                'team_id' => Filament::getTenant()->id,
            ],
            ['settings' => $this->columnWidths]
        );
    }
    public function mount(): void
    {
        if (blank($this->activeTab)) {
            $this->activeTab = 'Todas';
        }
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportar_excel')
                ->label('Exportar a Excel')
                ->icon('fas-file-excel')
                ->color('success')
                ->action(function ($livewire) {
                    $query = method_exists($livewire, 'getTableQueryForExport')
                        ? $livewire->getTableQueryForExport()
                        : (method_exists($livewire, 'getFilteredSortedTableQuery')
                            ? $livewire->getFilteredSortedTableQuery()
                            : $livewire->getFilteredTableQuery());

                    return $this->exportarPolizasExcel($query);
                }),
        ];
    }

    public function exportarPolizasExcel(?Builder $query = null)
    {
        $polizas = ($query ?? CatPolizas::query()
            ->where('team_id', Filament::getTenant()->id))
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Fecha',
            'Tipo',
            'Folio',
            'Concepto',
            'Referencia',
            'Cargos',
            'Abonos',
            'Periodo',
            'Ejercicio',
            'UUID',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $row = 2;
        foreach ($polizas as $poliza) {
            $fecha = $poliza->fecha ? date('Y-m-d', strtotime((string) $poliza->fecha)) : '';
            $referencia = filled($poliza->referencia) ? 'F-' . $poliza->referencia : '';

            $sheet->setCellValue('A' . $row, $fecha);
            $sheet->setCellValue('B' . $row, $poliza->tipo);
            $sheet->setCellValue('C' . $row, $poliza->folio);
            $sheet->setCellValue('D' . $row, $poliza->concepto);
            $sheet->setCellValue('E' . $row, $referencia);
            $sheet->setCellValue('F' . $row, (float) $poliza->cargos);
            $sheet->setCellValue('G' . $row, (float) $poliza->abonos);
            $sheet->setCellValue('H' . $row, $poliza->periodo);
            $sheet->setCellValue('I' . $row, $poliza->ejercicio);
            $sheet->setCellValue('J' . $row, $poliza->uuid);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'polizas_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'polizas_');
        $writer->save($tempFile);

        Notification::make()
            ->title('Pólizas exportadas')
            ->success()
            ->send();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function getTabs(): array
    {
        //$tabs = ['all' => Tab::make('All')->badge($this->getModel()::count())];
        return [
            'Todas'=>Tab::make('Todas')->modifyQueryUsing(function ($query){
                return $query->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio);
            }),
            'PG'=>Tab::make('Gastos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'PG')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'PV'=>Tab::make('Ventas')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'PV')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Dr'=>Tab::make('Diario')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Dr')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Ig'=>Tab::make('Ingresos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Ig')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'Eg'=>Tab::make('Egresos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('tipo', 'Eg')->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio);
                }),
            'OP'=>Tab::make('Otros Periodos')
                ->modifyQueryUsing(function ($query){
                    return $query->where('ejercicio',Filament::getTenant()->ejercicio);
                })
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'Todas';

    }


}
