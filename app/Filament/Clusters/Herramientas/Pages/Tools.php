<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\Admincuentaspagar;
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
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador']);
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
                        ->visible(function (){
                            $team = Filament::getTenant()->id;
                            $periodo = Filament::getTenant()->periodo;
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $per_team = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first();
                            $estado = $per_team?->estado ?? 1;
                            if($estado == 1) return true;
                            return false;
                        })
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
                    Actions\Action::make('Apertura de Periodo')
                        ->requiresConfirmation()
                        ->icon('fas-lock')
                        ->visible(function (){
                            $team = Filament::getTenant()->id;
                            $periodo = Filament::getTenant()->periodo;
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $per_team = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first();
                            $estado = $per_team?->estado ?? 1;
                            if($estado == 2) return true;
                            return false;
                        })
                        ->action(function (){
                            $team = Filament::getTenant()->id;
                            $periodo = Filament::getTenant()->periodo;
                            $ejercicio = Filament::getTenant()->ejercicio;
                            if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                            {
                                ContaPeriodos::create([
                                    'periodo'=>$periodo,
                                    'ejercicio'=>$ejercicio,
                                    'estado'=>1,
                                    'team_id'=>$team,
                                ]);
                            }
                            else{
                                ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)
                                    ->update(['estado'=>1]);
                            }
                        }),
                    Actions\Action::make('Alta de Proveedores')
                    ->action(function (){
                        try {
                            $cfdis = DB::table('almacencfdis')->where('xml_type', 'Recibidos')->where('TipoDeComprobante', 'I')->get();
                            foreach ($cfdis as $cfdi) {
                                if (!DB::table('proveedores')->where('team_id', $cfdi->team_id)->where('rfc', $cfdi->Emisor_Rfc)->exists()) {
                                    $clave = count(DB::table('proveedores')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    DB::table('proveedores')->insert([
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
                    Actions\Action::make('Cuentas Proveedores')
                    ->action(function (){
                        try {
                            $provees = DB::table('proveedores')->where('id', '>', 0)->get();
                            foreach ($provees as $provee) {

                                if (DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id', $provee->team_id)->exists()) {
                                    $ctaprove = DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '20101000')->where('team_id', $provee->team_id)->first();
                                    DB::table('proveedores')->where('id', $provee->id)->update(['cuenta_contable' => $ctaprove->codigo]);
                                    DB::table('cat_cuentas')->where('id', $ctaprove->id)->update(['rfc_asociado' => $provee->rfc]);
                                } else {
                                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '20101000')->max('codigo')) + 1;
                                    DB::table('cat_cuentas')->insert([
                                        'nombre' => $provee->nombre,
                                        'team_id' => $provee->team_id,
                                        'codigo' => $nuecta,
                                        'acumula' => '20101000',
                                        'tipo' => 'D',
                                        'naturaleza' => 'A',
                                        'rfc_asociado' => $provee->rfc
                                    ]);
                                    DB::table('proveedores')->where('id', $provee->id)->update(['cuenta_contable' => $nuecta]);
                                    Notification::make()->title('Proceso Completado')->success()->send();
                                }
                            }
                        }catch (\Exception $e){
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                    Actions\Action::make('Alta de Clientes')
                    ->action(function (){
                        try {
                            $cfdis = DB::table('almacencfdis')->where('xml_type', 'Emitidos')->where('TipoDeComprobante', 'I')->get();
                            foreach ($cfdis as $cfdi) {
                                if (!DB::table('clientes')->where('team_id', $cfdi->team_id)->where('rfc', $cfdi->Receptor_Rfc)->exists()) {
                                    $clave = count(DB::table('clientes')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    DB::table('clientes')->insert([
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
                    }),
                    Actions\Action::make('Cuentas Clientes')
                        ->action(function (){
                            try {
                                $provees = DB::table('clientes')->where('id', '>', 0)->get();
                                foreach ($provees as $provee) {

                                    if (DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->exists()) {
                                        $ctaprove = DB::table('cat_cuentas')->where('nombre', $provee->nombre)->where('acumula', '10501000')->where('team_id', $provee->team_id)->first();
                                        DB::table('clientes')->where('id', $provee->id)->update(['cuenta_contable' => $ctaprove->codigo]);
                                        DB::table('cat_cuentas')->where('id', $ctaprove->id)->update(['rfc_asociado' => $provee->rfc]);
                                    } else {
                                        $nuecta = intval(DB::table('cat_cuentas')->where('team_id', $provee->team_id)->where('acumula', '10501000')->max('codigo')) + 1;
                                        DB::table('cat_cuentas')->insert([
                                            'nombre' => $provee->nombre,
                                            'team_id' => $provee->team_id,
                                            'codigo' => $nuecta,
                                            'acumula' => '10501000',
                                            'tipo' => 'D',
                                            'naturaleza' => 'A',
                                            'rfc_asociado' => $provee->rfc
                                        ]);
                                        DB::table('clientes')->where('id', $provee->id)->update(['cuenta_contable' => $nuecta]);
                                        Notification::make()->title('Proceso Completado')->success()->send();
                                    }
                                }
                            }catch (\Exception $e){
                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Actions\Action::make('Genera CxP')
                    ->action(function (){
                        $Polizas = DB::table('cat_polizas')->where('tipo', 'PG')->get();
                        $resultado = [];
                        $errores = [];
                            foreach ($Polizas as $Poliza) {
                                try {
                                    $poliza = DB::table('cat_polizas')->where('id', $Poliza->id)->first();
                                    $cfdi = DB::table('almacencfdis')->where('id', $poliza->idcfdi)->first();
                                    $prov_ee = DB::table('proveedores')->where('rfc', $cfdi->Emisor_Rfc)->first();
                                    $cffecha = Carbon::parse($poliza->fecha)->format('Y-m-d');
                                    $cfecha_ven = Carbon::parse($poliza->fecha)->addDays(30)->format('Y-m-d');
                                    if (!DB::table('admincuentascobrars')->where('clave', $prov_ee->id)->where('referencia', $cfdi->id)->exists()) {
                                        $reg = DB::table('admincuentaspagars')->insertGetId([
                                            'clave' => $prov_ee->id,
                                            'referencia' => $cfdi->id,
                                            'uuid' => $cfdi->UUID,
                                            'fecha' => $cffecha,
                                            'vencimiento' => $cfecha_ven,
                                            'moneda' => $cfdi->Moneda,
                                            'tcambio' => $cfdi->TipoCambio,
                                            'importe' => $cfdi->Total * $cfdi->TipoCambio,
                                            'importeusd' => $cfdi->Total,
                                            'saldo' => $cfdi->Total * $cfdi->TipoCambio,
                                            'saldousd' => $cfdi->Total,
                                            'periodo' => $poliza->periodo,
                                            'ejercicio' => $poliza->ejercicio,
                                            'periodo_ven' => Carbon::create($cfecha_ven)->format('m'),
                                            'ejercicio_ven' => Carbon::create($cfecha_ven)->format('Y'),
                                            'poliza' => $poliza->id,
                                            'team_id' => $poliza->team_id,
                                        ]);

                                        $resultado[] = ['ID'=> $reg];
                                    }
                                }catch (\Exception $e){
                                    $errores[]=['error'=>$e->getMessage()];
                                }
                            }
                            Notification::make()->title('Proceso Completado')->success()->send();
                            dd($resultado,$errores);
                    }),
                    Actions\Action::make('Genera CxC')
                        ->action(function (){
                                $Polizas = DB::table('cat_polizas')->where('tipo', 'PV')->get();
                                $resultado = [];
                                $errores = [];
                                foreach ($Polizas as $Poliza) {
                                    try {
                                        $poliza = DB::table('cat_polizas')->where('id', $Poliza->id)->first();
                                        $cfdi = DB::table('almacencfdis')->where('id', $poliza->idcfdi)->first();
                                        $prov_ee = DB::table('clientes')->where('rfc', $cfdi->Receptor_Rfc)->first();
                                        $cffecha = Carbon::parse($poliza->fecha)->format('Y-m-d');
                                        $cfecha_ven = Carbon::parse($poliza->fecha)->addDays(30)->format('Y-m-d');
                                        if (!DB::table('admincuentascobrars')->where('clave', $prov_ee->id)->where('referencia', $cfdi->id)->exists()) {
                                            $reg = DB::table('admincuentascobrars')->insertGetId([
                                                'clave' => $prov_ee->id,
                                                'referencia' => $cfdi->id,
                                                'uuid' => $cfdi->UUID,
                                                'fecha' => $cffecha,
                                                'vencimiento' => $cfecha_ven,
                                                'moneda' => $cfdi->Moneda,
                                                'tcambio' => $cfdi->TipoCambio,
                                                'importe' => $cfdi->Total * $cfdi->TipoCambio,
                                                'importeusd' => $cfdi->Total,
                                                'saldo' => $cfdi->Total * $cfdi->TipoCambio,
                                                'saldousd' => $cfdi->Total,
                                                'periodo' => $poliza->periodo,
                                                'ejercicio' => $poliza->ejercicio,
                                                'periodo_ven' => Carbon::create($cfecha_ven)->format('m'),
                                                'ejercicio_ven' => Carbon::create($cfecha_ven)->format('Y'),
                                                'poliza' => $poliza->id,
                                                'team_id' => $poliza->team_id,
                                            ]);
                                            $resultado[] = ['ID'=> $reg];
                                        }
                                    }catch (\Exception $e) {
                                        $errores[]=['error'=>$e->getMessage()];
                                    }
                                }
                                Notification::make()->title('Proceso Completado')->success()->send();
                            dd($resultado,$errores);
                        })
                ])
            ]);
    }

}
