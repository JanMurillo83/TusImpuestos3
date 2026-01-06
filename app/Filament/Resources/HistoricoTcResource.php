<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoricoTcResource\Pages;
use App\Filament\Resources\HistoricoTcResource\RelationManagers;
use App\Models\HistoricoTc;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class HistoricoTcResource extends Resource
{
    protected static ?string $model = HistoricoTc::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Tipo de Cambio';
    protected static ?string $pluralLabel = 'Tipos de Cambio';
    protected static ?string $navigationIcon ='fas-calendar-day';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('fecha')
                    ->required()->default(Carbon::now()->format('Y-m-d')),
                Forms\Components\TextInput::make('tipo_cambio')
                    ->label('Tipo de cambio')
                    ->required()
                    ->numeric()
                    ->default(function (){
                        return DB::table('historico_tcs')->latest('id')->first()->tipo_cambio ?? 0;
                    }),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_cambio')
                    ->label('Tipo de cambio')
                    ->numeric()
                    ->sortable()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListHistoricoTcs::route('/'),
            //'create' => Pages\CreateHistoricoTc::route('/create'),
            //'edit' => Pages\EditHistoricoTc::route('/{record}/edit'),
        ];
    }
}
