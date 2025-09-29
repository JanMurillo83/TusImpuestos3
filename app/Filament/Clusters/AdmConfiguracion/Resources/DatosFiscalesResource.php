<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources;

use App\Filament\Clusters\AdmConfiguracion;
use App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource\Pages;
use App\Filament\Clusters\AdmConfiguracion\Resources\DatosFiscalesResource\RelationManagers;
use App\Models\DatosFiscales;
use App\Models\Regimenes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatosFiscalesResource extends Resource
{
    protected static ?string $model = DatosFiscales::class;
    protected static ?string $navigationIcon = 'fas-user-tag';
    protected static ?string $cluster = AdmConfiguracion::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255)->columnSpanFull(3),
                Forms\Components\TextInput::make('rfc')
                    ->maxLength(255),
                Forms\Components\Select::make('regimen')
                    ->options(Regimenes::all()->pluck('mostrar','clave')),
                Forms\Components\TextInput::make('codigo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('csdpass')
                    ->label('Password CSD')->password()->revealable(),
                Forms\Components\FileUpload::make('cer')
                    ->columnSpan(2)
                    ->directory(function (Get $get){
                        return 'CSDFiles/'.$get('rfc');
                    }),
                Forms\Components\FileUpload::make('key')
                    ->columnSpan(2)
                    ->directory(function (Get $get){
                        return 'CSDFiles/'.$get('rfc');
                    }),
                Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('direccion')
                    ->maxLength(255)->columnSpanFull(),
                Forms\Components\TextInput::make('telefono')
                    ->maxLength(255)->columnSpan(2),
                Forms\Components\TextInput::make('correo')
                    ->maxLength(255)->columnSpan(2),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre'),
                Tables\Columns\TextColumn::make('rfc'),
                Tables\Columns\TextColumn::make('regimen')
                    ->label('Regimen Fiscal'),
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Codigo Postal'),
                Tables\Columns\ImageColumn::make('logo64')
                    ->label('Logo')

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->label('')->icon(null)
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->after(function($record){
                    $logo = $record->logo;
                    $logo = storage_path('app/public/').$logo;
                    $type = pathinfo($logo, PATHINFO_EXTENSION);
                    $data = file_get_contents($logo);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    //dd($base64);
                    $record->logo64 = $base64;
                    $record->save();
                }),
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
            'index' => Pages\ListDatosFiscales::route('/'),
            //'create' => Pages\CreateDatosFiscales::route('/create'),
            //'edit' => Pages\EditDatosFiscales::route('/{record}/edit'),
        ];
    }
}
