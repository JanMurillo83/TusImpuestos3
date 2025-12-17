<?php

namespace App\Filament\Pages;

use App\Models\EstadCXC_F;
use App\Models\EstadCXC_F_F;
use Filament\Pages\Page;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

class Estadisticacxc_cliente extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estadisticacxc_cliente';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Cuentas por Cobrar';
    public function getTitle(): string
    {
        return '';
    }
    #[Url]
    public ?string $cliente = null;
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return EstadCXC_F_F::getCliente($this->cliente)->query();
            })
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('fecha','asc'))
            ->heading(new HtmlString("<h1 class='text-2xl font-bold' style='color: #301d1b; font-style: italic'>Cuentas por Cobrar - $this->cliente</h1>"))
            ->paginated(false)
            ->striped()
            ->recordClasses('row_gral')
            ->columns([
                TextColumn::make('factura'),
                TextColumn::make('fecha')->date('d-m-Y'),
                TextColumn::make('vencimiento')->date('d-m-Y'),
                TextColumn::make('importe')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight(),
                TextColumn::make('pagos')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight(),
                TextColumn::make('saldo')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight()
                ->summarize(Sum::make()->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')),
            ]);
    }
}
