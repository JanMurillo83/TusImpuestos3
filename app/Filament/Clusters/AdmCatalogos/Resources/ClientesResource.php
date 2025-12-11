<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources;

use App\Filament\Clusters\AdmCatalogos;
use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\Pages;
use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\Pages\ListClientes;
use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\RelationManagers;
use App\Livewire\CuentasCobrarWidget;
use App\Models\Clientes;
use App\Models\Regimenes;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ClientesResource extends Resource
{
    protected static ?string $model = Clientes::class;
    protected static ?string $navigationIcon = 'fas-users';
    protected static ?string $cluster = AdmCatalogos::class;
    protected static ?string $label = 'Cliente';
    protected static ?string $pluralLabel = 'Clientes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form

            ->extraAttributes(['style'=>'gap:0.3rem'])
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Fieldset::make('Fiscales')
                    ->extraAttributes(['style'=>'gap:0.3rem'])
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('clave')
                            ->required()
                            ->readOnly()
                            ->default(function(){
                                return count(Clientes::all()) + 1;
                            }),
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('rfc')
                            ->required()
                            ->maxLength(255)
                            ->default('XAXX010101000'),
                        Forms\Components\Select::make('regimen')
                            ->label('Regimen Fiscal')->required()
                            ->options(Regimenes::all()->pluck('mostrar','clave')),
                        Forms\Components\TextInput::make('codigo')
                            ->label('Codigo Postal')
                            ->maxLength(255)->required(),
                        Forms\Components\TextInput::make('dias_credito')
                            ->label('Dias de Credito')
                            ->numeric()->default(0)->required(),
                    ]),
                    Fieldset::make('Datos Generales')
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('correo')
                            ->maxLength(255)->required(),
                        Forms\Components\TextInput::make('contacto')
                            ->maxLength(255),
                        Forms\Components\Select::make('cuenta_contable')
                            ->label('Cuenta Contable')
                            ->searchable()
                            ->options(
                                DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                    ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                            ),
                        Forms\Components\Textarea::make('direccion')
                            ->maxLength(255)->columnSpanFull(),
                    ])->columns(3),
                Fieldset::make('Datos de Venta')
                    ->schema([
                        Forms\Components\TextInput::make('descuento')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->suffix('%'),
                        Forms\Components\Select::make('lista')
                            ->label('Lista de Precios')
                            ->options([
                                1 =>'Precio Publico',
                                2 =>'Lista de Precios 2',
                                3 =>'Lista de Precios 3',
                                4 =>'Lista de Precios 4',
                                5 =>'Lista de Precios 5'
                           ])->default(1),
                        Forms\Components\TextInput::make('saldo')
                            ->prefix('$')->readOnly()->default(0.00)
                        ->numeric()->currencyMask(decimalSeparator:'.',precision:2)
                    ])->columns(3)
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                ->numeric(decimalPlaces: 2,decimalSeparator:'.')
                ->prefix('$')
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()
                ->label('Editar')->icon('fas-edit')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->after(function($record){
                    $record->rfc = strtoupper($record->rfc);
                    $record->save();
                }),
                    Action::make('CxC')->label('Cuentas x Cobrar')
                        ->icon('fas-money-bill-transfer')
                        ->form(function($record){ return [
                            Forms\Components\Livewire::make(CuentasCobrarWidget::class,['cliente'=>$record->id])
                        ];
                    })->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
            ])->dropdownPlacement('top-end'),
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nuevo Cliente')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->after(function($record){
                    $record->rfc = strtoupper($record->rfc);
                    $record->save();
                }),
                Action::make('ImpProd')
                    ->label('Importar')
                    ->icon('fas-file-excel')->badge()
                    ->modalSubmitActionLabel('Importar')
                    ->modalCancelActionLabel('Cancelar')
                    ->form([
                        FileUpload::make('ExcelFile')
                        ->label('Seleccionar Archivo')
                        ->storeFiles(false)
                    ])->action(function($data){
                        //dd($data['ExcelFile']->path());
                        $archivo = $data['ExcelFile']->path();
                        $tipo=IOFactory::identify($archivo);
                        $lector=IOFactory::createReader($tipo);
                        $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
                        $hoja = $libro->getActiveSheet();
                        $rows = $hoja->toArray();
                        $r = 0;
                        $clientes =Clientes::all();
                        $clave = count($clientes) + 1;
                        foreach($rows as $row)
                        {
                            if($r > 0)
                            {
                                DB::table('clientes')->insert([
                                    'clave'=>$clave,
                                    'nombre'=>$row[0],
                                    'rfc'=>$row[1],
                                    'regimen'=>$row[2],
                                    'codigo'=>$row[3],
                                    'direccion'=>$row[7],
                                    'telefono'=>$row[4],
                                    'correo'=>$row[5],
                                    'descuento'=>$row[8],
                                    'lista'=>$row[9],
                                    'contacto'=>$row[6]
                                ]);
                            }
                            $r++;
                            $clave++;
                        }
                        Notification::make()
                        ->title('Registros Importados')
                        ->success()
                        ->send();
                })
            ],HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListClientes::route('/'),
            //'create' => Pages\CreateClientes::route('/create'),
            //'edit' => Pages\EditClientes::route('/{record}/edit'),
        ];
    }
}
