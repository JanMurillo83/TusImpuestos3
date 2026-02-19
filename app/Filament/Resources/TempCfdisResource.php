<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TempCfdisResource\Pages;
use App\Filament\Resources\TempCfdisResource\RelationManagers;
use App\Http\Controllers\NewCFDI;
use App\Http\Controllers\TempCfdisController;
use App\Models\Almacencfdis;
use App\Models\Team;
use App\Models\TempCfdis;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TempCfdisExport;

class TempCfdisResource extends Resource
{
    protected static ?string $model = TempCfdis::class;
    protected static ?string $navigationIcon = 'fas-building-circle-arrow-right';
    protected static ?string $navigationGroup = 'Operaciones CFDI';
    protected static ?string $navigationLabel = 'CFDI SAT';
    protected static ?string $label = 'CFDI SAT';
    protected static ?string $pluralLabel = 'CFDI SAT';
    protected static ?string $title = 'CFDI SAT';

    public function mount(): void
    {
        TempCfdis::where('team_id',Filament::getTenant()->id)->delete();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('UUID')
                    ->maxLength(255),
                Forms\Components\TextInput::make('RfcEmisor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('NombreEmisor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('RfcReceptor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('NombreReceptor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('RfcPac')
                    ->maxLength(255),
                Forms\Components\TextInput::make('FechaEmision')
                    ->maxLength(255),
                Forms\Components\TextInput::make('FechaCertificacionSat')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Monto')
                    ->numeric(),
                Forms\Components\TextInput::make('EfectoComprobante')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Estatus')
                    ->maxLength(255),
                Forms\Components\TextInput::make('FechaCancelacion')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Tipo')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('UUID')
                    ->searchable()->label('UUID')
                    ->limit(10)->searchable(),
                Tables\Columns\TextColumn::make('RfcEmisor')
                    ->searchable()->label('RFC Emisor')->searchable(),
                Tables\Columns\TextColumn::make('NombreEmisor')
                    ->searchable()->label('Nombre Emisor')->limit(20),
                Tables\Columns\TextColumn::make('RfcReceptor')
                    ->searchable()->label('RFC Receptor')->searchable(),
                Tables\Columns\TextColumn::make('NombreReceptor')
                    ->searchable()->label('Nombre Receptor')->limit(20)->searchable(),
                Tables\Columns\TextColumn::make('FechaEmision')
                    ->searchable()
                ->formatStateUsing(function ($state) {
                    $f = Carbon::parse($state);
                    return $f->format('d-m-Y');
                })->label('Fecha'),
                Tables\Columns\TextColumn::make('Monto')
                    ->prefix('$')
                    ->alignEnd()
                    ->numeric(decimalPlaces:2,decimalSeparator:'.')
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('EfectoComprobante')
                    ->searchable()->label('Tipo')->searchable(),
                Tables\Columns\TextColumn::make('Estatus')
                    ->searchable()->searchable(),
                Tables\Columns\TextColumn::make('Tipo')
                    ->label('E/R')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Existe')
                ->getStateUsing(function($record){
                    $Alm = Almacencfdis::where('UUID',$record['UUID'])
                        ->where('team_id',Filament::getTenant()->id)
                        ->exists();
                    return $Alm ? 'Si' : 'No';
                })
            ])->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\Filter::make('Existe')
                ->query(function (Builder $query){
                    $team_id = Filament::getTenant()->id;
                    return $query->whereNotIn('UUID', Almacencfdis::where('team_id', $team_id)->pluck('UUID'));
                })
            ])
            ->headerActions([
                Action::make('Consultar')
                    ->icon('fas-magnifying-glass')
                    ->form(function (Form $form){
                        return $form->schema([
                            Section::make('Consulta de Metadatos SAT')
                                ->description('Ingresa el período para consultar los CFDIs en el portal del SAT. Los datos de FIEL se obtienen automáticamente.')
                                ->icon('fas-satellite-dish')
                                ->schema([
                                    DatePicker::make('fecha_inicial')
                                        ->label('Fecha Inicial')
                                        ->required()
                                        ->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                                    DatePicker::make('fecha_final')
                                        ->label('Fecha Final')
                                        ->required()
                                        ->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                                    Placeholder::make('info_fiel')
                                        ->label('')
                                        ->content(function () {
                                            $team = Team::find(Filament::getTenant()->id);
                                            if (!$team) return '⚠️ No se encontró el equipo.';
                                            $estado = $team->estado_fiel ?? 'NO CONFIGURADA';
                                            $vigencia = $team->vigencia_fiel ? Carbon::parse($team->vigencia_fiel)->format('d/m/Y') : 'N/A';
                                            $icon = $estado === 'VALIDA' ? '✅' : '❌';
                                            return new \Illuminate\Support\HtmlString(
                                                "<div class='text-sm space-y-1'>"
                                                . "<div><strong>RFC:</strong> {$team->taxid}</div>"
                                                . "<div><strong>FIEL:</strong> {$icon} {$estado}</div>"
                                                . "<div><strong>Vigencia:</strong> {$vigencia}</div>"
                                                . "</div>"
                                            );
                                        }),
                                ]),
                        ]);
                    })
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('Consultar SAT')
                    ->modalIcon('fas-satellite-dish')
                    ->action(function (array $data){
                        set_time_limit(600);
                        $teamId = Filament::getTenant()->id;
                        $inicial = Carbon::parse($data['fecha_inicial'])->format('Y-m-d');
                        $final = Carbon::parse($data['fecha_final'])->format('Y-m-d');

                        // Validar que FIEL esté configurada
                        $team = Team::find($teamId);
                        if (!$team || $team->estado_fiel !== 'VALIDA') {
                            Notification::make()
                                ->title('FIEL no válida')
                                ->body('La FIEL no está configurada o no es válida. Verifique en Descargas SAT.')
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Notificación de inicio
                        Notification::make()
                            ->title('🔄 Conectando con el SAT (Descarga Masiva)...')
                            ->body("Solicitando metadatos del {$inicial} al {$final}. El SAT puede tardar varios minutos en procesar la solicitud.")
                            ->info()
                            ->send();

                        try {
                            $resultado = app(TempCfdisController::class)->consultarMetadatos($teamId, $inicial, $final);

                            if (!$resultado['success']) {
                                Notification::make()
                                    ->title('❌ Error en ' . $resultado['fase'])
                                    ->body($resultado['error'])
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                return;
                            }

                            if ($resultado['total'] === 0) {
                                Notification::make()
                                    ->title('Consulta Completada')
                                    ->body('No se encontraron CFDIs para el período seleccionado.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->title('✅ Consulta Completada')
                                ->body(
                                    "📄 Total: {$resultado['total']} registros\n"
                                    . "📤 Emitidos: {$resultado['emitidos']}\n"
                                    . "📥 Recibidos: {$resultado['recibidos']}"
                                )
                                ->success()
                                ->persistent()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Error inesperado')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('Mostrar')
            ->label('Mostrar Faltantes')
            ->action(function (Tables\Contracts\HasTable $livewire) {
                $livewire->resetTableFiltersForm(); // If you want to reset the filter before apply
                $livewire->tableFilters['Existe']['isActive'] = !$livewire->tableFilters['Existe']['isActive'];
                $livewire->updatedTableFilters();
            }),
                Action::make('Exportar')
            ->label('Exportar a Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                return Excel::download(new TempCfdisExport(Filament::getTenant()->id), 'cfdi-sat-' . date('Y-m-d') . '.xlsx');
            }),
            ],Tables\Actions\HeaderActionsPosition::Bottom)
            ->bulkActions([
                Tables\Actions\BulkAction::make('Descargar')
                ->icon('fas-download')
                ->action(function ($records){
                    set_time_limit(600);
                    $uuids = [];
                    foreach ($records as $record) {
                        if(!Almacencfdis::where('UUID',$record->UUID)->where('team_id',Filament::getTenant()->id)->exists()) {
                            $uuids[] = $record->UUID;
                        }
                    }
                    if(count($uuids)>0) {
                        Notification::make()
                            ->title('🔄 Descargando XMLs (Descarga Masiva)...')
                            ->body('Descargando '.count($uuids).' CFDIs del SAT. Este proceso puede tardar varios minutos.')
                            ->info()
                            ->send();

                        try {
                            $regs = app(NewCFDI::class)->Descarga(Filament::getTenant()->id, $uuids);
                            Notification::make()
                                ->title('✅ Descarga Completada')
                                ->body('Emitidos: '.$regs['data_emitidos'].' | Recibidos: '.$regs['data_recibidos'])
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Error en Descarga')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }else{
                        Notification::make()
                            ->title('Proceso Terminado')
                            ->body('No se encontraron registros nuevos para descargar')
                            ->warning()
                            ->send();
                    }
                })
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTempCfdis::route('/'),
            'create' => Pages\CreateTempCfdis::route('/create'),
            'edit' => Pages\EditTempCfdis::route('/{record}/edit'),
        ];
    }
}
