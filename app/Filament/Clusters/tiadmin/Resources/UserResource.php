<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\UserResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Role;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'fas-users';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Configuracion';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador']);
    }
    protected static ?string $label = 'Usuario';
    protected static ?string $pluralLabel = 'Usuarios';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('email_verified_at')
                ->default(Carbon::now()),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
                Forms\Components\Hidden::make('is_admin')
                    ->default('NO'),
                Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->options(Role::all()->pluck('description', 'name'))
                    ->required()
                    ->default(function ($record) {
                        // Get the first role of the user if it exists
                        if ($record && $record->roles->isNotEmpty()) {
                            return $record->roles->first()->name;
                        }
                        return null;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('team_id', Filament::getTenant()->id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('Agregar')
                ->icon('fas-plus')
                ->createAnother(false)
                ->after(function ($record){
                    $id = $record->getKey();
                    DB::table('team_user')->insert([
                        'user_id'=>$id,
                        'team_id'=>Filament::getTenant()->id
                    ]);

                    // Assign the selected role to the user
                    if ($record->role) {
                        $record->assignRole($record->role);
                    }
                })
            ],Tables\Actions\HeaderActionsPosition::Bottom)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->after(function ($record) {
                            // Update the user's role
                            if ($record->role) {
                                // Remove all existing roles first
                                $record->roles()->detach();
                                // Assign the new role
                                $record->assignRole($record->role);
                            }
                        }),
                ])
            ],Tables\Enums\ActionsPosition::BeforeCells);
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
            'index' => Pages\ListUsers::route('/'),
            //'create' => Pages\CreateUser::route('/create'),
            //'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
