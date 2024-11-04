<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovbancosResource\Pages;
use App\Filament\Resources\MovbancosResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\BancoCuentas;
use App\Models\Movbancos;
use CfdiUtils\Elements\Cfdi33\Comprobante;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum as MathTrigSum;

class MovbancosResource extends Resource
{
    protected static ?string $model = Movbancos::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Movimiento Bancario';
    protected static ?string $pluralLabel = 'Movimientos Bancarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->tabs([
                        Tabs\Tab::make('Datos Generales')
                            ->schema([
                                Forms\Components\DatePicker::make('fecha')
                                    ->required(),
                                Forms\Components\Select::make('tipo')
                                    ->required()
                                    ->options([
                                        'E'=>'Entrada',
                                        'S'=>'Salida'
                                    ]),
                                Forms\Components\Select::make('cuenta')
                                    ->required()
                                    ->label('Cuenta Bancaria')
                                        ->required()
                                        ->options(BancoCuentas::where('team_id',Filament::getTenant()->id)->pluck('banco','id')),
                                Forms\Components\TextInput::make('importe')
                                        ->required()
                                        ->numeric(),
                                Forms\Components\TextInput::make('concepto')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                Forms\Components\TextInput::make('ejercicio')
                                        ->default(Filament::getTenant()->ejercicio),
                                Forms\Components\TextInput::make('periodo')
                                        ->default(Filament::getTenant()->periodo),
                                Forms\Components\TextInput::make('contabilizada')
                                        ->required()
                                        ->maxLength(45)
                                        ->default('NO')
                                        ->readOnly(),
                            ])->columns(4),
                        Tabs\Tab::make('Datos del Comprobante')
                            ->schema([
                                Forms\Components\TextInput::make('tercero')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('factura')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('UUID')
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(1)->visible(
                                function(Get $get){
                                    $con = $get('contabilizada');
                                    if($con == 'NO')
                                        return false;
                                    else
                                        return true;
                                }
                            )
                    ])->columnSpanFull(),
                Forms\Components\Hidden::make('tax_id')
                    ->default(Filament::getTenant()->taxid),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable()
                    ->sortable()
                    ->state(function($record):string {
                        $v='';
                        if($record->tipo == 'E') $v = 'Entrada';
                        if($record->tipo == 'S') $v = 'Salida';
                        return $v;
                    }),
                Tables\Columns\TextColumn::make('tercero')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable()
                    ->sortable()
                    ->state(function($record):string {
                        $clientes = BancoCuentas::where('id',$record->cuenta)->get();
                        return $clientes[0]->banco;
                    }),
                Tables\Columns\TextColumn::make('factura')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('concepto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contabilizada')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Action::make('procesa_e')
                        ->form(function(Form $form){
                            return $form
                            ->schema([
                                TextInput::make('importe')
                                ->label('Importe Movimiento')
                                ->readOnly()
                                ->numeric()
                                ->prefix('$')
                                ->default(function(Model $record){
                                    return $record->importe;
                                }),
                                TextInput::make('importefactu')
                                ->label('Importe Facturas')
                                ->readOnly()
                                ->numeric()
                                ->prefix('$')
                                ->default(0),
                                Select::make('Movimiento')
                                    ->required()
                                    ->options([
                                        '1'=>'Cobro de Factura',
                                        '2'=>'Cobro no identificado',
                                        '3'=>'Prestamo Recibido',
                                        '4'=>'Otros Ingresos'
                                    ])->columnSpan(2),
                                Repeater::make('Facturas')
                                    ->columnSpanFull()
                                    ->itemLabel(fn (array $state): ?string => $state['UUID'] ?? null)
                                    ->collapsible()
                                    ->collapsed()
                                    ->reorderable(false)
                                    ->columns(4)
                                    ->schema([
                                        Select::make('Comprobante')
                                            ->searchable()
                                            ->options(
                                                function(){
                                                    $movimientos = Almacencfdis::where('xml_type','Emitidos')->get();
                                                    $regresar = [];
                                                    foreach($movimientos as $movimiento)
                                                    {
                                                        $idm = $movimiento->id;
                                                        $dats = 'Serie:'.$movimiento->Serie.' Folio:'.$movimiento->Folio.' UUID:'.$movimiento->UUID.' Receptor:'.$movimiento->Receptor_Nombre.' Importe:'.$movimiento->Total;
                                                        $datss = [$idm=>$dats];
                                                        array_push($regresar,$datss);
                                                    }
                                                    return $regresar;
                                                }
                                            )
                                            ->afterStateUpdated(function(Get $get,Set $set){
                                                $f = $get('Comprobante');
                                                $dt = Almacencfdis::where('id',$f)->get();
                                                //dd($dt);
                                                if(isset($dt[0]))
                                                {
                                                    $set('Fecha',$dt[0]->Fecha);
                                                    $set('Factura',$dt[0]->Serie.$dt[0]->Folio);
                                                    $set('Receptor',$dt[0]->Receptor_Nombre);
                                                    $set('Importe',$dt[0]->Total);
                                                    $set('UUID',$dt[0]->UUID);
                                                    Self::sumas($get,$set);
                                                }
                                                else
                                                {
                                                    $set('Fecha','');
                                                    $set('Factura','');
                                                    $set('Receptor','');
                                                    $set('Importe','');
                                                    $set('UUID','');
                                                }
                                            })
                                            ->live(onBlur: true)
                                            ->columnSpanFull(),
                                            TextInput::make('Fecha')
                                            ->readOnly(),
                                            TextInput::make('Factura')
                                            ->readOnly(),
                                            Hidden::make('UUID'),
                                            TextInput::make('Receptor')
                                            ->readOnly(),
                                            TextInput::make('Importe')
                                            ->readOnly(),
                                        ])
                            ])->columns(4);
                        })
                        ->modalWidth('7xl')
                        ->visible(fn ($record) => $record->tipo == 'E')
                        ->label('Procesar')
                        ->accessSelectedRecords()
                        ->icon('fas-check-to-slot')
                        ->action(function (Model $record,$data) {
                            Self::procesa_e_f($record,$data);
                        }),
                    Action::make('procesa_s')
                        ->form([
                            Select::make('Movimiento')
                            ->options([
                                '1'=>'Pago de Factura',
                                '2'=>'Reembolso de Gastos',
                                '3'=>'Compra de Activo',
                                '4'=>'Prestamo Entregado',
                                '5'=>'Gasto No deducible'
                            ]),

                        ])->modalWidth(MaxWidth::Small)
                        ->visible(fn ($record) => $record->tipo == 'S')
                        ->label('Procesar')
                        ->accessSelectedRecords()
                        ->icon('fas-check-to-slot')
                        ->action(function (Model $record) {
                            Self::procesa_s_f($this,$record);
                        })
                ])->color('primary')
            ])->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function sumas(Get $get,Set $set) :void
    {
        $f = collect($get('Facturas'))->pluck('Importe')->sum();
        dd($f);
        if(isset($f))
        {
            $set('../../importefactu',$f);
        }
        else{
            $set('../../importefactu',0);
        }
    }
    public static function procesa_e_f($record,$data)
    {
        dd($data);
    }

    public static function procesa_s_f($record,$data)
    {
        dd($data['Movimiento']);
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
            'index' => Pages\ListMovbancos::route('/'),
            //'create' => Pages\CreateMovbancos::route('/create'),
            //'edit' => Pages\EditMovbancos::route('/{record}/edit'),
        ];
    }
}

