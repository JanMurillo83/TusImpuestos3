<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources;

use App\Filament\Clusters\AdmCatalogos;
use App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource\Pages;
use App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource\RelationManagers;
use App\Models\Proveedores;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
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
use Filament\Forms\Components\Hidden;
use Filament\Facades\Filament;

class ProveedoresResource extends Resource
{
    protected static ?string $model = Proveedores::class;
    protected static ?string $navigationIcon = 'fas-users-line';
    protected static ?string $cluster = AdmCatalogos::class;
    protected static ?string $label = 'Proveedor';
    protected static ?string $pluralLabel = 'Proveedores';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Forms\Components\TextInput::make('clave')
                    ->required()
                    ->readOnly()
                    ->default(function(){
                        return count(Proveedores::all()) + 1;
                    }),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)->columnSpan(2),
                Forms\Components\TextInput::make('rfc')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contacto')
                    ->maxLength(255),
                Forms\Components\Textarea::make('direccion')
                    ->maxLength(255)->columnSpanFull(),
                Forms\Components\TextInput::make('telefono')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('correo')
                    ->maxLength(255),

            ])->columns(3);
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ->tooltip('Nuevo Proveedor')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left),
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
                    $clientes =Proveedores::all();
                    $clave = count($clientes) + 1;
                    foreach($rows as $row)
                    {
                        if($r > 0)
                        {
                            DB::table('proveedores')->insert([
                               'clave'=>$clave,
                               'nombre'=>$row[0],
                               'rfc'=>$row[1],
                               'direccion'=>$row[2],
                               'telefono'=>$row[3],
                               'correo'=>$row[4],
                               'contacto'=>$row[5]
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
            'index' => Pages\ListProveedores::route('/'),
            //'create' => Pages\CreateProveedores::route('/create'),
            //'edit' => Pages\EditProveedores::route('/{record}/edit'),
        ];
    }
}
