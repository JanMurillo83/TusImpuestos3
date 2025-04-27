<?php

namespace App\Filament\Clusters\ContReportes\Resources;

use App\Filament\Clusters\ContReportes;
use App\Filament\Clusters\ContReportes\Resources\ListareportesResource\Pages;
use App\Filament\Clusters\ContReportes\Resources\ListareportesResource\RelationManagers;
use App\Http\Controllers\NuevoReportes;
use App\Http\Controllers\ReportesController;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\Listareportes;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Torgodly\Html2Media\Tables\Actions\Html2MediaAction;

class ListareportesResource extends Resource
{
    protected static ?string $model = Listareportes::class;
    protected static ?string $navigationIcon = 'fas-print';
    //protected static ?string $cluster = ContReportes::class;
    //protected static bool $isScopedToTenant = false;
    protected static ?string $label = 'Reporte';
    protected static ?string $pluralLabel = 'Reportes';
    public static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ruta')
                    ->maxLength(255),
                Forms\Components\Select::make('tipo')
                    ->options(['Conta'=>'Contabilidad','IVA'=>'Reportes de IVA',
                    'ISR'=>'Reportes de ISR','Fina'=>'Financieros']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->visible(false),
                Tables\Columns\TextColumn::make('descripcion'),
                Tables\Columns\TextColumn::make('ruta')
                    ->visible(false),
                Tables\Columns\TextColumn::make('tipo')
            ])->headerActions([
                Html2MediaAction::make('Balance General')
                    ->button()
                    ->preview()
                    ->print(false)
                    ->savePdf()
                    ->filename('Balance General')
                    ->content(fn()=>view('BalanceGral',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio]))
            ],Tables\Actions\HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListListareportes::route('/'),
            //'create' => Pages\CreateListareportes::route('/create'),
            //'edit' => Pages\EditListareportes::route('/{record}/edit'),
        ];
    }
}
