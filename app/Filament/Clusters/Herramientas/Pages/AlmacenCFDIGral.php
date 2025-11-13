<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\Almacencfdis;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AlmacenCFDIGral extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-database';

    protected static string $view = 'filament.clusters.herramientas.pages.almacen-c-f-d-i-gral';

    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Almacen CFDI';
    protected static ?string $navigationLabel = 'Almacen CFDI';
    protected static ?int $navigationSort = 4;


    public function table(Table $table): Table
    {
        return $table
            ->query(Almacencfdis::query())
            ->columns([
                TextColumn::make('team_id')
                    ->formatStateUsing(function ($state) {
                        return Team::find($state)->name;
                    }),
                TextColumn::make('Fecha')
                    ->searchable()
                    ->sortable()
                    ->date('d-m-Y'),
                TextColumn::make('Serie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Folio')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $truncatedValue = Str::limit($state, 10);
                        return new HtmlString("<span title='{$state}'>{$truncatedValue}</span>");
                    })
                    ->action(Action::make('Folio')->form([
                        TextInput::make('Folio')
                            ->hiddenLabel()->readOnly()
                            ->default(function ($record) {
                                return $record->Folio;
                            })
                    ])),
                TextColumn::make('Moneda')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('TipoDeComprobante')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Receptor_Rfc')
                    ->label('RFC Receptor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Receptor_Nombre')
                    ->label('Nombre Receptor')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('Moneda')
                    ->label('Moneda')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('TipoCambio')
                    ->label('T.C.')
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(function (string $state) {
                        if ($state <= 0) $state = 1;
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                TextColumn::make('Total')
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                TextColumn::make('used')
                    ->label('Asociado')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('UUID')
                    ->label('UUID')
                    ->formatStateUsing(function ($state) {
                        $truncatedValue = Str::limit($state, 10);
                        return new HtmlString("<span title='{$state}'>{$truncatedValue}</span>");
                    })
                    ->action(Action::make('UUID')->form([
                        TextInput::make('UUID')
                            ->hiddenLabel()->readOnly()
                            ->default(function ($record) {
                                return $record->UUID;
                            })
                    ])),
                TextColumn::make('MetodoPago')
                    ->label('M. Pago')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('FormaPago')
                    ->label('F. Pago')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('xml_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ejercicio')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('periodo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notas')
                    ->label('Refer.')
                    ->searchable()
                    ->sortable(),
            ]);
    }
}
