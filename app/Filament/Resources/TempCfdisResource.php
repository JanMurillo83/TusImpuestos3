<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TempCfdisResource\Pages;
use App\Filament\Resources\TempCfdisResource\RelationManagers;
use App\Http\Controllers\NewCFDI;
use App\Models\Almacencfdis;
use App\Models\Team;
use App\Models\TempCfdis;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
                            Section::make('Filtro')
                                ->schema([
                                    DatePicker::make('fecha_inicial')
                                        ->label('Fecha Inicial')
                                        ->default(Carbon::now()->format('Y-m-d')),
                                    DatePicker::make('fecha_final')
                                        ->label('Fecha Final')
                                        ->default(Carbon::now()->format('Y-m-d')),
                                ])
                        ]);
                    })
                    ->modalWidth('sm')
                    ->modalSubmitActionLabel('Consultar')
                    ->action(function (array $data){
                        app(NewCFDI::class)->borrar(Filament::getTenant()->id);
                        $inicial = Carbon::parse($data['fecha_inicial'])->format('Y-m-d');
                        $final = Carbon::parse($data['fecha_final'])->format('Y-m-d');
                        $resultado = app(NewCFDI::class)->Scraper(Filament::getTenant()->id,$inicial,$final);
                        $no_emitidos = $resultado['emitidos'];
                        $no_recibidos = $resultado['recibidos'];
                        $data_emitidos = $resultado['data_emitidos'];
                        $data_recibidos = $resultado['data_recibidos'];
                        if($no_emitidos==0 && $no_recibidos==0) {
                            Notification::make()
                                ->title('Proceso Terminado')
                                ->body('NO se encontraron registros para la fecha seleccionada')
                                ->danger()->send();
                            return;
                        }
                        $all_data = [];
                        foreach ($data_emitidos as $data) {
                            /** @var \PhpCfdi\CfdiSatScraper\Metadata $data */
                            $all_data[] = [
                                "UUID" => $data->uuid(),
                                "RfcEmisor" => $data->rfcEmisor,
                                "NombreEmisor" => $data->nombreEmisor,
                                "RfcReceptor" => $data->rfcReceptor,
                                "NombreReceptor" => $data->nombreReceptor,
                                "RfcPac" => $data->pacCertifico,
                                "FechaEmision" => $data->fechaEmision,
                                "FechaCertificacionSat" => $data->fechaCertificacion,
                                "Monto" => floatval(str_replace([',','$'],['',''],$data->total)),
                                "EfectoComprobante" => $data->efectoComprobante,
                                "Estatus" => $data->estadoComprobante,
                                "FechaCancelacion" => $data->fechaDeCancelacion,
                                "Tipo" => 'Emitidos',
                                "team_id" => Filament::getTenant()->id
                            ];
                        }
                        foreach ($data_recibidos as $data) {
                            /** @var \PhpCfdi\CfdiSatScraper\Metadata $data */
                            $all_data[]=[
                                "UUID" => $data->uuid(),
                                "RfcEmisor" => $data->rfcEmisor,
                                "NombreEmisor" => $data->nombreEmisor,
                                "RfcReceptor" => $data->rfcReceptor,
                                "NombreReceptor" => $data->nombreReceptor,
                                "RfcPac" => $data->pacCertifico,
                                "FechaEmision" => $data->fechaEmision,
                                "FechaCertificacionSat" => $data->fechaCertificacion,
                                "Monto" => floatval(str_replace([',','$'],['',''],$data->total)),
                                "EfectoComprobante" => $data->efectoComprobante,
                                "Estatus" => $data->estadoComprobante,
                                "FechaCancelacion" => $data->fechaDeCancelacion,
                                "Tipo" => 'Recibidos',
                                "team_id" => Filament::getTenant()->id
                            ];
                        }

                        $regs = app(NewCFDI::class)->graba($all_data);
                        Notification::make()
                            ->title('Proceso Terminado')
                            ->body('Se han procesado '.$regs.'Registros Totales - '.$no_emitidos.' emitidos y '.$no_recibidos.' recibidos')
                            ->success()->send();
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
                    $uuids = [];
                    foreach ($records as $record) {
                        if(!Almacencfdis::where('UUID',$record->UUID)->where('team_id',Filament::getTenant()->id)->exists()) {
                            $uuids[] = $record->UUID;
                        }
                    }
                    if(count($uuids)>0) {
                        $regs = app(NewCFDI::class)->Descarga(Filament::getTenant()->id,$uuids);
                        Notification::make()
                            ->title('Proceso Terminado')
                            ->body('Se han descargado '.$regs['data_emitidos'].' registros Emitidos y '.$regs['data_recibidos'].' Recibidos')
                            ->success()
                            ->send()->persistent();
                    }else{
                        Notification::make()
                            ->title('Proceso Terminado')
                            ->body('No se encontraron registros nuevos para descargar')
                            ->success()
                            ->send()->persistent();
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
