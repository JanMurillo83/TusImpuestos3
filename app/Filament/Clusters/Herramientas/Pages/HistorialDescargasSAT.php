<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\ValidaDescargas;
use App\Models\Team;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class HistorialDescargasSAT extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-history';
    protected static string $view = 'filament.clusters.herramientas.pages.historial-descargas-s-a-t';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Historial de Descargas SAT';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole(['administrador']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ValidaDescargas::query()->with('team')->orderBy('fecha', 'desc'))
            ->defaultPaginationPageOption(10)
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('team.name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),
                TextColumn::make('team.taxid')
                    ->label('RFC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->label('Fecha Ejecución')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->timezone('America/Mexico_City'),
                TextColumn::make('inicio')
                    ->label('Inicio Período')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fin')
                    ->label('Fin Período')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('emitidos')
                    ->label('Emitidos')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('recibidos')
                    ->label('Recibidos')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->searchable()
                    ->wrap()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Completado') => 'success',
                        str_contains($state, 'Error') => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('team_id')
                    ->label('RFC')
                    ->options(Team::where('descarga_cfdi', 'SI')->pluck('taxid', 'id'))
                    ->searchable(),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Completado' => 'Completado',
                        'Error' => 'Con Errores',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'Completado') {
                            return $query->where('estado', 'Completado');
                        }
                        if ($data['value'] === 'Error') {
                            return $query->where('estado', '!=', 'Completado');
                        }
                        return $query;
                    }),
                Filter::make('fecha')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Desde'),
                        DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Acciones individuales si las necesitas
            ])
            ->bulkActions([
                // Acciones masivas si las necesitas
            ]);
    }
}
