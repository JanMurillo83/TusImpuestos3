<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\CatCuentas;
use App\Models\ContaPeriodos;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class Tools extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;
    protected static ?string $navigationIcon = 'fas-tools';
    protected static string $view = 'filament.clusters.herramientas.pages.tools';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Herramientas';

    protected static function setShouldRegisterNavigation(): bool
    {
        if(auth()->id() == 1) return true;
        return false;
    }

    public ? int $periodo;
    public ? int $ejercicio;
    protected function getActions(): array
    {
        return [
            Html2MediaAction::make('Imprimir_Doc_E')
                ->visible(false)
                ->print(false)
                ->savePdf()
                ->preview(true)
                ->margin([0,0,0,2])
                ->content(fn() => view('ReporteTiembres',['periodo'=>$this->periodo,'ejercicio'=>$this->ejercicio]))
                ->modalWidth('7xl')
                ->filename('Reporte de Timbres')
        ];
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Actions::make([
                    Actions\Action::make('Reporte de Timbres')
                    ->form([
                        Select::make('periodo')
                        ->options([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'])
                        ->default(Carbon::now()->month),
                        Select::make('ejercicio')
                        ->options([2024=>2024,2025=>2025,2026=>2026,2027=>2027,2028=>2028,2029=>2029,2030=>2030])
                        ->default(Carbon::now()->year),
                    ])
                    ->action(function ($livewire,array $data){
                        //dd($data);
                        $this->periodo = intval($data['periodo']);
                        $this->ejercicio = intval($data['ejercicio']);
                        $livewire->getAction('Imprimir_Doc_E')->visible(true);
                        $livewire->replaceMountedAction('Imprimir_Doc_E');
                        $livewire->getAction('Imprimir_Doc_E')->visible(false);
                    }),
                    Actions\Action::make('Alta masiva de Cuenta')
                    ->form([
                        TextInput::make('cuenta'),
                        TextInput::make('nombre'),
                        TextInput::make('acumula'),
                        Select::make('tipo')->options(['A'=>'Acumulativa','D'=>'Detalle']),
                        Select::make('naturaleza')->options(['D'=>'Deudora','A'=>'Acreedora']),
                        TextInput::make('csat')->label('Clave SAT'),
                    ])
                    ->action(function (array $data){
                        $teams = DB::table('teams')->get();
                        foreach ($teams as $team) {
                            if(!CatCuentas::where('codigo',$data['cuenta'])->where('team_id',$team->id)->exists()) {
                                CatCuentas::create([
                                    'codigo' => $data['cuenta'],
                                    'nombre' => $data['nombre'],
                                    'acumula' => $data['acumula'],
                                    'tipo' => $data['tipo'],
                                    'naturaleza' => $data['naturaleza'],
                                    'csat' => $data['csat'],
                                    'team_id' => $team->id
                                ]);
                            }
                        }
                    }),
                    Actions\Action::make('Cierre de Periodo')
                    ->requiresConfirmation()
                    ->icon('fas-lock')
                    ->action(function (){
                        $team = Filament::getTenant()->id;
                        $periodo = Filament::getTenant()->periodo;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                        {
                            ContaPeriodos::create([
                                'periodo'=>$periodo,
                                'ejercicio'=>$ejercicio,
                                'estado'=>2,
                                'team_id'=>$team,
                            ]);
                        }
                        else{
                            ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)
                            ->update(['estado'=>2]);
                        }
                    }),
                    Actions\Action::make('Alta de Proveedores')
                    ->action(function (){
                        try {
                            $cfdis = \App\Models\Almacencfdis::where('xml_type', 'Recibidos')->where('TipoDeComprobante', 'I')->get();
                            foreach ($cfdis as $cfdi) {
                                if (!DB::table('proveedores')->where('team_id', $cfdi->team_id)->where('rfc', $cfdi->Emisor_Rfc)->exists()) {
                                    $clave = count(DB::table('proveedores')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    \App\Models\Proveedores::create([
                                        'clave' => $clave,
                                        'rfc' => $cfdi->Emisor_Rfc,
                                        'nombre' => $cfdi->Emisor_Nombre,
                                        'team_id' => $cfdi->team_id,
                                        'dias_credito' => 30
                                    ]);
                                }
                            }
                            \Illuminate\Support\Facades\DB::statement('UPDATE proveedores SET dias_credito = 30 WHERE id > 0');
                            Notification::make()->title('Proceso Completado')->success()->send();
                        }catch(\Exception $e){
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                    Actions\Action::make('Alta de Clientes')
                    ->action(function (){
                        try {
                            $cfdis = \App\Models\Almacencfdis::where('xml_type', 'Emitidos')->where('TipoDeComprobante', 'I')->get();
                            foreach ($cfdis as $cfdi) {
                                if (!DB::table('clientes')->where('team_id', $cfdi->team_id)->where('rfc', $cfdi->Receptor_Rfc)->exists()) {
                                    $clave = count(DB::table('clientes')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    \App\Models\Clientes::create([
                                        'clave' => $clave,
                                        'rfc' => $cfdi->Receptor_Rfc,
                                        'nombre' => $cfdi->Receptor_Nombre,
                                        'team_id' => $cfdi->team_id,
                                        'dias_credito' => 30
                                    ]);
                                }
                            }
                            \Illuminate\Support\Facades\DB::statement('UPDATE clientes SET dias_credito = 30 WHERE id > 0');
                            Notification::make()->title('Proceso Completado')->success()->send();
                        }catch(\Exception $e){
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    })
                ])
            ]);
    }

}
