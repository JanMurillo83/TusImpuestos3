<?php

namespace App\Filament\Clusters\ContReportes\Resources;

use App\Filament\Clusters\ContReportes;
use App\Filament\Clusters\ContReportes\Resources\ListareportesResource\Pages;
use App\Filament\Clusters\ContReportes\Resources\ListareportesResource\RelationManagers;
use App\Http\Controllers\NuevoReportes;
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

class ListareportesResource extends Resource
{
    protected static ?string $model = Listareportes::class;
    protected static ?string $navigationIcon = 'fas-print';
    protected static ?string $cluster = ContReportes::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $label = 'Reporte';
    protected static ?string $pluralLabel = 'Reportes';

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
            ])
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordAction('Reporte')
            ->actions([
                Action::make('Reporte')
                ->label('')
                ->form([
                    Section::make('Filtro')
                        ->schema([
                            Select::make('periodo')
                            ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12]
                            )->default(Filament::getTenant()->periodo),
                            Select::make('ejercicio')
                            ->options(function()
                            {
                                $ant = Filament::getTenant()->ejercicio;
                                $f = $ant - 10;
                                $t = $ant + 11;
                                $pers = [];
                                for($i=$f;$i<$t;$i++)
                                {
                                    $pers[$i] =$i;
                                }
                                return $pers;
                            })
                            ->default(Filament::getTenant()->ejercicio),
                            Toggle::make('ConS')->label('Solo con Saldos')
                            ->visible(function($record){
                                if($record->nombre == 'Balanza') return true;
                                else return false;
                            }),
                        ])->columns(2)
                ])
                ->modalWidth('md')->modalSubmitActionLabel('Generar')
                ->action(function($data,$livewire,$record){
                    $empresa = Filament::getTenant()->id;
                    $periodo = $data['periodo'];
                    $ejercicio =$data['ejercicio'];
                    $livewire->contabilizar($empresa,$periodo,$ejercicio);
                    switch($record->nombre)
                    {
                        case 'Balanza':
                            $page = env('APP_URL').'reportes/contabilidad/balanza?empresa='.$empresa.'&periodo='.$periodo.'&ejercicio='.$ejercicio;
                        break;
                        case 'Balance':
                            $page = env('APP_URL').'reportes/contabilidad/balance?empresa='.$empresa.'&periodo='.$periodo.'&ejercicio='.$ejercicio;
                        break;
                        case 'Estado':
                            $page = env('APP_URL').'reportes/contabilidad/estado?empresa='.$empresa.'&periodo='.$periodo.'&ejercicio='.$ejercicio;
                        break;
                    }
                    $livewire->js('window.open("'.$page.'","socialPopupWindow")');
                })
            ])
            ->actionsPosition(ActionsPosition::BeforeCells);
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
