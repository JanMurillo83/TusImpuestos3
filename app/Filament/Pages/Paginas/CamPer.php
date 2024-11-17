<?php

namespace App\Filament\Pages\Paginas;

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
                TextInput::make('periodo')
                    ->numeric()
                    ->default(Filament::getTenant()->periodo),
                TextInput::make('ejercicio')
                    ->numeric()
                    ->default(Filament::getTenant()->ejercicio),
            ])->modalWidth(MaxWidth::ExtraSmall)
            ->modalCancelAction(false)
            ->action(function($livewire,$data){
                $livewire->cambia_per($data);
            });
    }
    public function cambia_per($data)
    {
        DB::table('teams')->where('id',Filament::getTenant()->id)->update([
            'periodo'=>$data['periodo'],
            'ejercicio'=>$data['ejercicio'],
        ]);
        Notification::make()
            ->title(' Periodo de Trabajo Actualizado')
            ->success()
            ->send();
            redirect('/main/'.Filament::getTenant()->id);
    }

}
