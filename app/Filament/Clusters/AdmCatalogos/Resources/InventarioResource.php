<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources;

use App\Filament\Clusters\AdmCatalogos;
use App\Filament\Clusters\AdmCatalogos\Resources\InventarioResource\Pages;
use App\Filament\Clusters\AdmCatalogos\Resources\InventarioResource\RelationManagers;
use App\Models\Claves;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Lineasprod;
use App\Models\Movinventario;
use App\Models\Unidades;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpParser\Node\Expr\FuncCall;
use Filament\Forms\Components\Hidden;
use Filament\Facades\Filament;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;
    protected static ?string $navigationIcon = 'fas-boxes-stacked';
    protected static ?string $cluster = AdmCatalogos::class;
    protected static ?string $label = 'Producto';
    protected static ?string $pluralLabel = 'Productos';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Fieldset::make('Generales')
                ->schema([
                Forms\Components\TextInput::make('clave')
                    ->label('Sku')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpan(3),
                Forms\Components\Select::make('linea')
                    ->options(Lineasprod::all()->pluck('descripcion','id'))
                    ->default(1),
                Forms\Components\TextInput::make('marca')
                    ->maxLength(255),
                Forms\Components\TextInput::make('modelo')
                    ->maxLength(255),
                Forms\Components\Select::make('servicio')
                    ->options(['SI'=>'SI','NO'=>'NO'])
                    ->default('NO')
                    ->required(),
                ])->columns(4),
                Fieldset::make('Compras')
                    ->schema([
                Forms\Components\TextInput::make('u_costo')
                    ->label('Ultimo Costo')
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->required()
                    ->prefix('$')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('p_costo')
                    ->label('Costo Promedio')
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('exist')
                    ->label('Existencia')
                    ->readOnly()
                    ->numeric()
                    ->default(0.00000000)
                ])->columns(3),
                Fieldset::make('Ventas')
                ->schema([
                Forms\Components\TextInput::make('precio1')
                    ->label('Precio Publico')
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio2')
                    ->required()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio3')
                    ->required()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio4')
                    ->required()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio5')
                    ->required()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\Select::make('esquema')
                    ->label('Esquema de Impuestos')
                    ->required()
                    ->options(DB::table('esquemasimps')->pluck('descripcion','id'))
                    ->default(1),
                Forms\Components\Select::make('unidad')
                    ->label('Unidad de Medida')
                    ->searchable()
                    ->required()
                    ->options(Unidades::all()->pluck('mostrar','clave'))
                    ->default('H87'),
                Forms\Components\TextInput::make('cvesat')
                    ->label('Clave SAT')
                    ->default('01010101')
                    ->required()
                    ->suffixAction(
                        Action::make('Cat_cve_sat')
                            ->label('Buscador')
                            ->icon('fas-circle-question')
                            ->form([
                                Forms\Components\Select::make('CatCveSat')
                                    ->default(function(Get $get): string{
                                        if($get('cvesat'))
                                            $val = $get('cvesat');
                                        else
                                            $val = '01010101';
                                        return $val;
                                    })
                            ->label('Claves SAT')
                            ->searchable()
                            ->searchDebounce(100)
                            ->getSearchResultsUsing(fn (string $search): array => Claves::where('mostrar', 'like', "%{$search}%")->limit(50)->pluck('mostrar', 'clave')->toArray())
                        ])
                        ->modalCancelAction(false)
                        ->modalSubmitActionLabel('Seleccionar')
                        ->modalWidth('sm')
                        ->action(function(Set $set,$data){
                            $set('cvesat',$data['CatCveSat']);
                        })
                    ),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('linea')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function(Model $record){
                        $lin = $record->linea;
                        $linea = Lineasprod::where('id',$lin)->get();
                        $linea = $linea[0];
                        return $linea->descripcion;
                    }),
                Tables\Columns\TextColumn::make('marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('precio1')
                    ->label('Precio Publico')
                    ->prefix('$')
                    ->numeric(decimalPlaces:2,decimalSeparator:'.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exist')
                    ->label('Existencia')
                    ->numeric()
                    ->sortable()
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
                ->modalFooterActionsAlignment(Alignment::Left),
            ])
            ->headerActions([
                CreateAction::make('Agregar')
                    ->createAnother(false)
                    ->tooltip('Nuevo Producto')
                    ->label('Agregar')->icon('fas-circle-plus')->badge()
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
                ActionsAction::make('ImpProd')
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
                    $clientes =Inventario::all();
                    $clave = count($clientes) + 1;
                    foreach($rows as $row)
                    {
                        if($r > 0)
                        {
                            $linea = Lineasprod::firstOrCreate(
                                ['clave' => $row[2]],
                                ['descripcion' => $row[2]]
                            );
                            DB::table('inventarios')->insert([
                               'clave'=>$row[0],
                               'descripcion'=>$row[1],
                               'linea'=>$linea->id,
                               'marca'=>$row[3],
                               'modelo'=>$row[4],
                               'u_costo'=>$row[5],
                               'p_costo'=>$row[6],
                               'precio1'=>$row[7],
                               'precio2'=>$row[8],
                               'precio3'=>$row[9],
                               'precio4'=>$row[10],
                               'precio5'=>$row[11],
                               'exist'=>$row[12],
                               'esquema'=>$row[13],
                               'servicio'=>$row[14],
                               'unidad'=>$row[15],
                               'cvesat'=>$row[16]
                            ]);
                        }
                        $r++;
                        $clave++;
                    }
                    Notification::make()
                    ->title('Registros Importados')
                    ->success()
                    ->send();
            }),
            ActionsAction::make('Fisico')
                ->label('Inventario Fisico')
                ->icon('fas-boxes-packing')->badge()
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Cerrar')
                ->form([
                    Actions::make([
                    Action::make('Leer Inventario')
                    ->action(function(Get $get,Set $set){
                        $inve = Inventario::where('servicio','NO')->get();
                        $productos =[];
                        foreach($inve as $prod)
                        {
                            $prods = [
                                'SKU'=>$prod->clave,
                                'Descripcion'=>$prod->descripcion,
                                'Existencia'=>$prod->exist,
                                'Conteo'=>$prod->exist,
                                'Diferencia'=>0
                            ];
                            array_push($productos,$prods);
                        }
                        $set('productos',$productos);
                        //dd($get('productos'));
                    }),
                    Action::make('Exportar')
                    ->action(function(Get $get){
                        $archivo = storage_path('app/public/Fisico.xlsx');
                        if(File::exists($archivo)) unlink($archivo);
                        $invfisico = $get('productos');
                        $titulo = ['SKU'=>'SKU',
                                'Descripcion'=>'Descripcion',
                                'Existencia'=>'Existencia',
                                'Conteo'=>'Conteo'
                            ];
                        array_unshift($invfisico,$titulo);
                        //------------------------------------
                        $libro = new Spreadsheet();
                        $libro->removeSheetByIndex(0);
                        $hoja = new Worksheet($libro,'InvFisico');
                        $libro->addSheet($hoja,0);
                        $hoja->fromArray($invfisico);
                        $Writer = new Xlsx($libro);
                        $Writer->save($archivo);
                        //------------------------------------
                        Notification::make()
                        ->title('Archivo Exportado')
                        ->success()->send();
                        return response()->download($archivo);
                    }),
                    Action::make('Importar')
                        ->modalSubmitActionLabel('Importar')
                        ->modalCancelActionLabel('Cancelar')
                        ->form([
                            FileUpload::make('ExcelFile')
                            ->label('Seleccionar Archivo')
                            ->storeFiles(false)
                        ])->action(function($data,Set $set){
                            //dd($data['ExcelFile']->path());
                            $archivo = $data['ExcelFile']->path();
                            $tipo=IOFactory::identify($archivo);
                            $lector=IOFactory::createReader($tipo);
                            $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
                            $hoja = $libro->getActiveSheet();
                            $rows = $hoja->toArray();
                            $r = 0;
                            $productos = [];
                            foreach($rows as $row)
                            {
                                if($r > 0)
                                {
                                    $produ = Inventario::where('clave',$row[0])->get();

                                    if(count($produ)>0){
                                        $prod = $produ[0];
                                        $dife = $row[3] - $prod->exist;
                                        $prods = [
                                            'SKU'=>$row[0],
                                            'Descripcion'=>$prod->descripcion,
                                            'Existencia'=>$prod->exist,
                                            'Conteo'=>$row[3],
                                            'Diferencia'=>$dife
                                        ];
                                        array_push($productos,$prods);
                                    }
                                }
                                $r++;
                            }
                            $set('productos',$productos);
                        })]),
                    TableRepeater::make('productos')
                    ->emptyLabel('No existen Registros')
                    ->streamlined()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->headers([
                        Header::make('SKU'),
                        Header::make('Descripcion'),
                        Header::make('Existencia'),
                        Header::make('Conteo'),
                        Header::make('Diferencia'),
                    ])->schema([
                        TextInput::make('SKU')->readOnly(),
                        TextInput::make('Descripcion')->readOnly(),
                        TextInput::make('Existencia')->readOnly(),
                        TextInput::make('Conteo')->default(0),
                        TextInput::make('Diferencia')->default(0),
                    ])
                ])->action(function($data){
                    $productos = $data['productos'];
                    foreach($productos as $prod)
                    {
                        $clave = $prod['SKU'];
                        $conteo = $prod['Conteo'];
                        $dife = $prod['Diferencia'];
                        if($dife != 0)
                        {
                            $invens = Inventario::where('clave',$clave)->get();
                            $inve = $invens[0];
                            Inventario::where('id',$inve->id)->update(['exist'=>$conteo]);
                            if($dife > 0)
                            {
                                Movinventario::insert([
                                    'producto'=>$inve->id,
                                    'tipo'=>'Entrada',
                                    'fecha'=>Carbon::now(),
                                    'cant'=>$dife,
                                    'costo'=>$inve->p_costo,
                                    'precio'=>0,
                                    'concepto'=>3,
                                    'tipoter'=>'N',
                                    'tercero'=>0
                                ]);
                            }
                            if($dife < 0)
                            {
                                Movinventario::insert([
                                    'producto'=>$inve->id,
                                    'tipo'=>'Salida',
                                    'fecha'=>Carbon::now(),
                                    'cant'=>($dife*-1),
                                    'costo'=>$inve->p_costo,
                                    'precio'=>0,
                                    'concepto'=>7,
                                    'tipoter'=>'N',
                                    'tercero'=>0
                                ]);
                            }
                        }
                        Notification::make()
                        ->title('Proceso Concluido')
                        ->success()->send();
                    }
                })
            ],HeaderActionsPosition::Bottom)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListInventarios::route('/'),
            //'create' => Pages\CreateInventario::route('/create'),
            //'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}
