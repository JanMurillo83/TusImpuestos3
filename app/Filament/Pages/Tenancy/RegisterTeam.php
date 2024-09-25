<?php

namespace App\Filament\Pages\Tenancy;


use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Registro de Empresa';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Razon Social'),
                TextInput::make('taxid')
                    ->label('RFC'),
                FileUpload::make('archivokey')
                    ->label('Archivo KEY'),
                FileUpload::make('archivocer')
                    ->label('Archivo CER'),
                TextInput::make('fielpass')
                    ->label('Password Fiel')
                    ->password(),
                TextInput::make('periodo')
                    ->label('Perido de Trabajo')
                    ->numeric()
                    ->default(1),
                TextInput::make('ejercicio')
                    ->label('Ejercicio de Trabajo')
                    ->numeric()
                    ->default(2024)
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);
        DB::table('team_user')->insert([
            'user_id'=>auth()->user()->id,
            'team_id'=>$team->getKey()
        ]);
        //$team->members()->attach(auth()->user());

        return $team;
    }
    protected function getFotterActions(): array
    {
        return [
            Action::make('edit'),
            Action::make('delete'),
        ];
    }

}
