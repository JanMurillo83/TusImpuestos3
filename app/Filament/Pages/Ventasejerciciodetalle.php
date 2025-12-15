<?php

namespace App\Filament\Pages;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Http\Controllers\MainChartsController;
use App\Models\AuxVentas;
use App\Models\AuxVentasEjercicio;
use Fibtegis\FilamentInfiniteScroll\Concerns\InteractsWithInfiniteScroll;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class Ventasejerciciodetalle extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.ventasejerciciodetalle';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Ventas  del Ejercicio';
    public ?string $file_title = '';

    public function getTitle(): string
    {
        return '';
    }
    public function table(Table $table): Table
    {
        return $table
            ->query( AuxVentasEjercicio::query())
            ->heading(function (){
                $mes = Filament::getTenant()->periodo;
                $anio = Filament::getTenant()->ejercicio;
                $empresa = Filament::getTenant()->name;
                $letras = app(MainChartsController::class)->mes_letras($mes);
                $titulo = new HtmlString("<h2 class='text-2xl font-bold'>Ventas del Ejericicio $anio</h2>");
                $this->file_title = 'Ventas del Ejercicio '.$anio.' '.$empresa;
                return $titulo;
            })
            ->paginated(false)
            ->striped()
            ->columns([
                TextColumn::make('Poliza')
                    ->getStateUsing(fn ($record) => $record->tipo.$record->folio),
                TextColumn::make('fecha')->date('d-m-Y'),
                TextColumn::make('concepto'),
                TextColumn::make('factura'),
                TextColumn::make('uuid')->label('UUID'),
                TextColumn::make('abono')->label('Importe')
                    ->numeric(2,'.',',')
                    ->prefix('$')
                    ->summarize(
                        Sum::make()->numeric(2,'.',',')
                            ->label('Total')
                            ->prefix('$')
                    )
                    ->alignment(Alignment::Right),
            ])->headerActions([
                FilamentExportHeaderAction::make('Excel')
                    ->label('')
                    ->fileName($this->file_title)
                    ->icon('fas-file-excel')->iconButton()
                    ->defaultFormat('xlsx')
                    ->directDownload(true)->color('success'),
                FilamentExportHeaderAction::make('PDF')
                    ->label('')
                    ->fileName($this->file_title)
                    ->icon('fas-file-pdf')->iconButton()
                    ->defaultFormat('pdf')
                    ->directDownload(true)->color('danger')
                    ->defaultPageOrientation('landscape'),
            ]);
    }
}
