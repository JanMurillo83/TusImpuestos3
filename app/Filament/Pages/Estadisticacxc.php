<?php

namespace App\Filament\Pages;

use App\Http\Controllers\MainChartsController;
use App\Models\EstadCXC;
use App\Models\EstadCXC_F;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class Estadisticacxc extends Page implements  HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estadisticacxc';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Cuentas por Cobrar';

    public function getTitle(): string
    {
        return '';
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(EstadCXC_F::query())
            ->modifyQueryUsing(fn ($query) => $query->orderBy('saldo','desc'))
            ->heading(new HtmlString('<h1 class="text-2xl font-bold" style="color: #301d1b; font-style: italic">Cuentas por Cobrar</h1>'))
            ->paginated(false)
            ->striped()
            ->recordClasses('row_gral')
            ->columns([
                TextColumn::make('clave'),
                TextColumn::make('cliente'),
                TextColumn::make('saldo')
                ->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight()
                ->summarize(Sum::make()->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$'))
            ])->recordUrl(function ($record) {
                $cliente = $record->clave;
                $ruta = Estadisticacxc_cliente::getUrl(['cliente'=>$cliente]);
                return $ruta;
            });
    }
}
