<?php

namespace App\Filament\Pages\Paginas;

use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\MaxWidth;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class CamPer extends Page
{
    protected static ?string $navigationIcon = 'fas-calendar-check';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.paginas.cam-per';
    protected static bool $shouldRegisterNavigation = false;
    public $defaultAction = 'onboarding';
    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->modalHeading('Seleccion de Periodo')
            ->form([
                Select::make('empresa')
                    ->options(DB::table('teams')->pluck('name','id'))
                    ->default(Filament::getTenant()->id)
                    ->searchable(),
                TextInput::make('periodo')
                    ->numeric()->minValue(1)->maxValue(13)
                    ->default(Filament::getTenant()->periodo),
                TextInput::make('ejercicio')
                    ->numeric()
                    ->default(Filament::getTenant()->ejercicio),
            ])->modalWidth(MaxWidth::Small)
            ->modalCancelAction(false)
            ->action(function($livewire,$data){
                $livewire->cambia_per($data);
            });
    }
    public function cambia_per($data)
    {
        DB::table('teams')->where('id',$data['empresa'])->update([
            'periodo'=>$data['periodo'],
            'ejercicio'=>$data['ejercicio'],
        ]);
        Notification::make()
            ->title(' Periodo de Trabajo Actualizado')
            ->success()
            ->send();
            redirect('/'.$data['empresa']);
    }

}
