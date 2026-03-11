<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\CatPolizas;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Filament\Notifications\Notification;

class PolizasDescuadradas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $cluster = Herramientas::class;

    protected static ?string $title = 'Pólizas Descuadradas';

    protected static ?string $navigationLabel = 'Pólizas Descuadradas';

    protected static string $view = 'filament.clusters.herramientas.pages.polizas-descuadradas';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (! empty($user->is_admin)) {
            return true;
        }

        if (($user->role ?? null) && in_array($user->role, ['administrador', 'admin', 'contador'], true)) {
            return true;
        }

        return method_exists($user, 'hasRole')
            ? $user->hasRole(['administrador', 'admin', 'contador'])
            : false;
    }

    public function table(Table $table): Table
    {
        $team_id = Filament::getTenant()->id;

        return $table
            ->query(
                CatPolizas::query()
                    ->select('cat_polizas.*')
                    ->selectRaw("CONCAT(ejercicio, '-', LPAD(periodo, 2, '0')) as periodo_grupo")
                    ->where('cat_polizas.team_id', $team_id)
                    ->addSelect([
                        'sum_cargos' => DB::table('auxiliares')
                            ->whereColumn('cat_polizas_id', 'cat_polizas.id')
                            ->selectRaw('SUM(COALESCE(cargo, 0))'),
                        'sum_abonos' => DB::table('auxiliares')
                            ->whereColumn('cat_polizas_id', 'cat_polizas.id')
                            ->selectRaw('SUM(COALESCE(abono, 0))'),
                        'invalid_accounts_count' => DB::table('auxiliares')
                            ->whereColumn('cat_polizas_id', 'cat_polizas.id')
                            ->where(function($query) {
                                $query->whereNull('codigo')
                                    ->orWhere('codigo', '')
                                    ->orWhereNotExists(function($q) {
                                        $q->select(DB::raw(1))
                                            ->from('cat_cuentas')
                                            ->whereColumn('cat_cuentas.codigo', 'auxiliares.codigo')
                                            ->whereColumn('cat_cuentas.team_id', 'cat_polizas.team_id');
                                    });
                            })
                            ->selectRaw('COUNT(*)'),
                    ])
                    ->where(function ($query) {
                        $sumCargos = '(select SUM(COALESCE(cargo, 0)) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id`)';
                        $sumAbonos = '(select SUM(COALESCE(abono, 0)) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id`)';
                        $invalidAccounts = '(select COUNT(*) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id` and (`codigo` is null or `codigo` = \'\' or not exists (select 1 from `cat_cuentas` where `cat_cuentas`.`codigo` = `auxiliares`.`codigo` and `cat_cuentas`.`team_id` = `cat_polizas`.`team_id`)))';

                        $query->whereRaw('ROUND(COALESCE(cat_polizas.cargos, 0), 2) != ROUND(COALESCE(cat_polizas.abonos, 0), 2)')
                            ->orWhereRaw("ROUND(COALESCE($sumCargos, 0), 2) != ROUND(COALESCE($sumAbonos, 0), 2)")
                            ->orWhereRaw("ROUND(COALESCE(cat_polizas.cargos, 0), 2) != ROUND(COALESCE($sumCargos, 0), 2)")
                            ->orWhereRaw("ROUND(COALESCE(cat_polizas.abonos, 0), 2) != ROUND(COALESCE($sumAbonos, 0), 2)")
                            ->orWhereRaw("$invalidAccounts > 0");
                    })
            )
            ->columns([
                TextColumn::make('ejercicio')
                    ->sortable(),
                TextColumn::make('periodo')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
                        default => $state,
                    }),
                TextColumn::make('tipo')
                    ->sortable()
                    ->badge(),
                TextColumn::make('folio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('concepto')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('cargos_header')
                    ->label('Cabecera (C/A)')
                    ->getStateUsing(fn ($record) => number_format($record->cargos, 2) . ' / ' . number_format($record->abonos, 2))
                    ->color(fn ($record) => round($record->cargos, 2) != round($record->abonos, 2) ? 'danger' : 'gray'),
                TextColumn::make('aux_totals')
                    ->label('Auxiliares (C/A)')
                    ->getStateUsing(fn ($record) => number_format($record->sum_cargos, 2) . ' / ' . number_format($record->sum_abonos, 2))
                    ->color(fn ($record) => round($record->sum_cargos, 2) != round($record->sum_abonos, 2) ? 'danger' : 'gray'),
                TextColumn::make('errors')
                    ->label('Observaciones')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $errors = [];
                        if (round($record->cargos, 2) != round($record->abonos, 2)) {
                            $errors[] = 'Cabecera Descuadrada';
                        }
                        if (round($record->sum_cargos, 2) != round($record->sum_abonos, 2)) {
                            $errors[] = 'Auxiliares Descuadrados';
                        }
                        if (round($record->cargos, 2) != round($record->sum_cargos, 2) || round($record->abonos, 2) != round($record->sum_abonos, 2)) {
                            $errors[] = 'Cabecera != Auxiliares';
                        }
                        if ($record->invalid_accounts_count > 0) {
                            $errors[] = 'Cuentas Inválidas/Vacías (' . $record->invalid_accounts_count . ')';
                        }
                        return $errors;
                    })
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('ejercicio')
                    ->options(fn () => CatPolizas::where('team_id', Filament::getTenant()->id)->distinct()->pluck('ejercicio', 'ejercicio')->toArray()),
                SelectFilter::make('periodo')
                    ->options([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ]),
            ])
            ->headerActions([
                Action::make('autocorregir_todas')
                    ->label('Autocorregir Todas')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Autocorregir pólizas descuadradas')
                    ->modalDescription('Se recalcularán cargos y abonos desde auxiliares y se actualizará la cabecera de todas las pólizas listadas como descuadradas.')
                    ->modalSubmitActionLabel('Autocorregir')
                    ->action(function () {
                        $teamId = Filament::getTenant()->id;

                        $polizas = CatPolizas::query()
                            ->select('cat_polizas.id')
                            ->where('cat_polizas.team_id', $teamId)
                            ->where(function ($query) {
                                $sumCargos = '(select SUM(COALESCE(cargo, 0)) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id`)';
                                $sumAbonos = '(select SUM(COALESCE(abono, 0)) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id`)';
                                $invalidAccounts = '(select COUNT(*) from `auxiliares` where `cat_polizas_id` = `cat_polizas`.`id` and (`codigo` is null or `codigo` = \'\' or not exists (select 1 from `cat_cuentas` where `cat_cuentas`.`codigo` = `auxiliares`.`codigo` and `cat_cuentas`.`team_id` = `cat_polizas`.`team_id`)))';

                                $query->whereRaw('ROUND(COALESCE(cat_polizas.cargos, 0), 2) != ROUND(COALESCE(cat_polizas.abonos, 0), 2)')
                                    ->orWhereRaw("ROUND(COALESCE($sumCargos, 0), 2) != ROUND(COALESCE($sumAbonos, 0), 2)")
                                    ->orWhereRaw("ROUND(COALESCE(cat_polizas.cargos, 0), 2) != ROUND(COALESCE($sumCargos, 0), 2)")
                                    ->orWhereRaw("ROUND(COALESCE(cat_polizas.abonos, 0), 2) != ROUND(COALESCE($sumAbonos, 0), 2)")
                                    ->orWhereRaw("$invalidAccounts > 0");
                            })
                            ->get();

                        if ($polizas->isEmpty()) {
                            Notification::make()
                                ->title('Sin pólizas descuadradas')
                                ->warning()
                                ->send();
                            return;
                        }

                        $actualizadas = 0;
                        foreach ($polizas as $poliza) {
                            $totales = DB::table('auxiliares')
                                ->where('cat_polizas_id', $poliza->id)
                                ->selectRaw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos')
                                ->first();

                            CatPolizas::where('id', $poliza->id)->update([
                                'cargos' => $totales->cargos ?? 0,
                                'abonos' => $totales->abonos ?? 0,
                            ]);

                            $actualizadas++;
                        }

                        Notification::make()
                            ->title('Autocorrección completada')
                            ->success()
                            ->body("Se actualizaron {$actualizadas} pólizas.")
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Corregir')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (CatPolizas $record): string => \App\Filament\Resources\CatPolizasResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('ejercicio', 'desc')
            ->defaultGroup(
                Group::make('periodo_grupo')
                    ->label('Ejercicio y Periodo')
                    ->getTitleFromRecordUsing(fn (CatPolizas $record): string => $record->ejercicio . ' - ' . match ($record->periodo) {
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                        default => $record->periodo,
                    })
            )
            ->groups([
                'ejercicio',
                'periodo',
            ]);
    }
}
