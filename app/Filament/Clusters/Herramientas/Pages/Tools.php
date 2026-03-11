<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Filament\Clusters\Herramientas\Pages\PolizasDescuadradas;
use App\Models\Admincuentaspagar;
use App\Models\CatCuentas;
use App\Models\ContaPeriodos;
use App\Services\Herramientas\CatalogosImportService;
use App\Services\Herramientas\CatalogosLayoutService;
use App\Services\Herramientas\CatalogosResetService;
use App\Services\Herramientas\MovimientosResetService;
use App\Services\PolizaCierreService;
use App\Services\SaldosCache;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (! empty($user->is_admin)) {
            return true;
        }

        if (($user->role ?? null) && in_array($user->role, ['administrador', 'admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasRole')
            ? $user->hasRole(['administrador', 'admin'])
            : false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
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
                    Actions\Action::make('repararEmpresa')
                        ->label('Reparar Empresa')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reparar Empresa')
                        ->modalDescription('Ejecutará: 1) Corregir Cuentas Duplicadas, 2) Corregir Naturaleza de Cuentas, 3) Habilitar periodo 13, validar/crear cuentas de cierre y generar póliza de cierre en periodo 13, 4) Recontabilizar saldos.')
                        ->modalSubmitActionLabel('Ejecutar')
                        ->form([
                            Select::make('ejercicio')
                                ->label('Ejercicio')
                                ->options(function () {
                                    $anioActual = date('Y');
                                    $opciones = [];
                                    for ($i = $anioActual - 5; $i <= $anioActual + 1; $i++) {
                                        $opciones[$i] = $i;
                                    }
                                    return $opciones;
                                })
                                ->default(Filament::getTenant()->ejercicio)
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            $tenant = Filament::getTenant();
                            if (! $tenant) {
                                Notification::make()->title('No se encontró el tenant activo.')->danger()->send();
                                return;
                            }

                            $teamId = $tenant->id;
                            $ejercicio = (int) ($data['ejercicio'] ?? $tenant->ejercicio);
                            $resumen = [];

                            try {
                                // 1) Corregir Cuentas Duplicadas
                                $code = Artisan::call('app:corregir-cuentas-duplicadas');
                                $resumen[] = $code === 0
                                    ? '✓ Cuentas duplicadas corregidas'
                                    : '⚠ Error al corregir cuentas duplicadas';

                                // 2) Corregir Naturaleza de Cuentas
                                $code = Artisan::call('cuentas:validar-naturalezas', [
                                    '--team_id' => $teamId,
                                    '--corregir' => true,
                                    '--no-interaction' => true,
                                ]);
                                $resumen[] = $code === 0
                                    ? '✓ Naturaleza de cuentas validada/corregida'
                                    : '⚠ Error al corregir naturaleza de cuentas';

                                // 3) Habilitar periodo 13, validar/crear cuentas de cierre y generar póliza
                                $polizaService = app(PolizaCierreService::class);
                                $polizaService->habilitarPeriodoAjuste($teamId, $ejercicio);
                                $this->asegurarCuentasCierre($teamId);

                                try {
                                    $polizaService->generarPolizaCierre($teamId, $ejercicio, 13, '30401000');
                                    $resumen[] = "✓ Póliza de cierre generada en periodo 13 ({$ejercicio})";
                                } catch (\Exception $e) {
                                    $msg = $e->getMessage();
                                    if (str_contains($msg, 'Ya existe una póliza de cierre')) {
                                        $resumen[] = "ℹ Ya existe póliza de cierre en periodo 13 ({$ejercicio})";
                                    } else {
                                        $resumen[] = "⚠ Error al generar póliza de cierre: {$msg}";
                                    }
                                }

                                // 4) Recontabilizar Saldos
                                $reconta = $this->recontabilizarSaldos($teamId, $ejercicio, null);
                                $resumen[] = "✓ Recontabilización completada: {$reconta['cuentas']} cuentas, {$reconta['periodos']} periodos, {$reconta['errores']} errores";

                                Notification::make()
                                    ->title('Reparación de empresa completada')
                                    ->success()
                                    ->body(implode("\n", $resumen))
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error en Reparar Empresa', [
                                    'team_id' => $teamId,
                                    'ejercicio' => $ejercicio,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error al reparar empresa')
                                    ->danger()
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ])->columnSpanFull(),
                Actions::make([
                    Actions\Action::make('polizasDescuadradas')
                        ->label('Ver Pólizas Descuadradas')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->url(PolizasDescuadradas::getUrl()),
                ]),
                Actions::make([
                    Actions\Action::make('importarCatalogos')
                        ->label('Importar catálogos (Inventario / Clientes / Proveedores)')
                        ->icon('fas-file-import')
                        ->requiresConfirmation()
                        ->modalHeading('Importar catálogos')
                        ->modalSubmitActionLabel('Procesar')
                        ->form([
                            Actions::make([
                                Actions\Action::make('layoutInventario')
                                    ->label('Descargar layout Inventario')
                                    ->icon('fas-download')
                                    ->action(function () {
                                        $service = app(CatalogosLayoutService::class);
                                        $csv = "\xEF\xBB\xBF" . $service->toCsv('inventario');

                                        return response()->streamDownload(
                                            function () use ($csv) {
                                                echo $csv;
                                            },
                                            $service->filename('inventario'),
                                            ['Content-Type' => 'text/csv; charset=UTF-8']
                                        );
                                    }),
                                Actions\Action::make('layoutClientes')
                                    ->label('Descargar layout Clientes')
                                    ->icon('fas-download')
                                    ->action(function () {
                                        $service = app(CatalogosLayoutService::class);
                                        $csv = "\xEF\xBB\xBF" . $service->toCsv('clientes');

                                        return response()->streamDownload(
                                            function () use ($csv) {
                                                echo $csv;
                                            },
                                            $service->filename('clientes'),
                                            ['Content-Type' => 'text/csv; charset=UTF-8']
                                        );
                                    }),
                                Actions\Action::make('layoutProveedores')
                                    ->label('Descargar layout Proveedores')
                                    ->icon('fas-download')
                                    ->action(function () {
                                        $service = app(CatalogosLayoutService::class);
                                        $csv = "\xEF\xBB\xBF" . $service->toCsv('proveedores');

                                        return response()->streamDownload(
                                            function () use ($csv) {
                                                echo $csv;
                                            },
                                            $service->filename('proveedores'),
                                            ['Content-Type' => 'text/csv; charset=UTF-8']
                                        );
                                    }),
                            ])->columnSpanFull(),

                            Toggle::make('eliminar_movimientos')
                                ->label('Eliminar movimientos antes de importar (Mov bancarios, Cotizaciones, Facturas, OC, Órdenes de Insumos)')
                                ->default(false),

                            Toggle::make('eliminar_inventario')
                                ->label('Eliminar catálogo de Inventario')
                                ->default(false),
                            Toggle::make('eliminar_clientes')
                                ->label('Eliminar catálogo de Clientes')
                                ->default(false),
                            Toggle::make('eliminar_proveedores')
                                ->label('Eliminar catálogo de Proveedores')
                                ->default(false),

                            FileUpload::make('inventario_file')
                                ->label('Archivo de Inventario (CSV/XLSX)')
                                ->storeFiles(false)
                                ->acceptedFileTypes([
                                    'text/csv',
                                    'text/plain',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ]),
                            FileUpload::make('clientes_file')
                                ->label('Archivo de Clientes (CSV/XLSX)')
                                ->storeFiles(false)
                                ->acceptedFileTypes([
                                    'text/csv',
                                    'text/plain',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ]),
                            FileUpload::make('proveedores_file')
                                ->label('Archivo de Proveedores (CSV/XLSX)')
                                ->storeFiles(false)
                                ->acceptedFileTypes([
                                    'text/csv',
                                    'text/plain',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ]),
                        ])
                        ->action(function (array $data) {
                            $tenant = Filament::getTenant();
                            if (! $tenant) {
                                Notification::make()->title('No se encontró el tenant activo.')->danger()->send();
                                return;
                            }

                            $teamId = $tenant->id;

                            $movimientosCounts = [];
                            $catalogosCounts = [];

                            DB::transaction(function () use ($teamId, $data, &$movimientosCounts, &$catalogosCounts) {
                                if (! empty($data['eliminar_movimientos'])) {
                                    $movimientosCounts = app(MovimientosResetService::class)->purgeForTeam($teamId);
                                }

                                $catalogosCounts = app(CatalogosResetService::class)->purgeForTeam($teamId, [
                                    'inventario' => ! empty($data['eliminar_inventario']),
                                    'clientes' => ! empty($data['eliminar_clientes']),
                                    'proveedores' => ! empty($data['eliminar_proveedores']),
                                ]);
                            });

                            $paths = [
                                'inventario' => ! empty($data['inventario_file']) ? $data['inventario_file']->path() : null,
                                'clientes' => ! empty($data['clientes_file']) ? $data['clientes_file']->path() : null,
                                'proveedores' => ! empty($data['proveedores_file']) ? $data['proveedores_file']->path() : null,
                            ];

                            $importResult = app(CatalogosImportService::class)->import($teamId, $paths);

                            $lines = [];
                            if (! empty($movimientosCounts)) {
                                $lines[] = 'Movimientos eliminados: ' . json_encode($movimientosCounts);
                            }
                            if (! empty($catalogosCounts)) {
                                $lines[] = 'Catálogos eliminados: ' . json_encode($catalogosCounts);
                            }

                            foreach (['inventario' => 'Inventario', 'clientes' => 'Clientes', 'proveedores' => 'Proveedores'] as $key => $label) {
                                if (! empty($importResult[$key])) {
                                    $r = $importResult[$key];
                                    $lines[] = $label . ': ' . ($r['created'] ?? 0) . ' creados, ' . ($r['updated'] ?? 0) . ' actualizados, ' . ($r['skipped'] ?? 0) . ' omitidos.';
                                }
                            }

                            if (empty($lines)) {
                                $lines[] = 'No se realizaron cambios (sin archivos y sin opciones de eliminación seleccionadas).';
                            }

                            Notification::make()
                                ->title('Proceso completado')
                                ->body(implode("\n", $lines))
                                ->success()
                                ->send();
                        }),
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
                                $rfc = strtoupper(trim($cfdi->Emisor_Rfc));
                                if ($rfc === '') {
                                    continue;
                                }
                                if (!DB::table('proveedores')->where('team_id', $cfdi->team_id)->where(DB::raw('UPPER(rfc)'), $rfc)->exists()) {
                                    $clave = count(DB::table('proveedores')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    DB::table('proveedores')->insert([
                                        'clave' => $clave,
                                        'rfc' => $rfc,
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
                                $rfc = strtoupper(trim($cfdi->Receptor_Rfc));
                                if ($rfc === '') {
                                    continue;
                                }
                                if (!DB::table('clientes')->where('team_id', $cfdi->team_id)->where(DB::raw('UPPER(rfc)'), $rfc)->exists()) {
                                    $clave = count(DB::table('clientes')->where('team_id', $cfdi->team_id)->get()) + 1;
                                    DB::table('clientes')->insert([
                                        'clave' => $clave,
                                        'rfc' => $rfc,
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
                        }),
                    Actions\Action::make('Validar Naturalezas')
                        ->icon('fas-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Validar y Corregir Naturalezas de Cuentas')
                        ->modalDescription('Este proceso validará y corregirá las naturalezas de las cuentas contables de TODOS LOS EQUIPOS según las reglas estándar (Activo=D, Pasivo=A, Capital=A, Ingresos=A, Costos=D, Gastos=D). Las pólizas NO serán modificadas.')
                        ->modalSubmitActionLabel('Validar y Corregir Todos')
                        ->action(function (){
                            try {
                                // Ejecutar para todos los teams (sin parámetro --team_id)
                                Artisan::call('cuentas:validar-naturalezas', [
                                    '--corregir' => true,
                                    '--no-interaction' => true
                                ]);

                                $output = Artisan::output();

                                // Extraer información del output
                                preg_match('/Cuentas con naturaleza incorrecta: (\d+)/', $output, $matches);
                                $incorrectas = $matches[1] ?? 0;

                                if ($incorrectas > 0) {
                                    Notification::make()
                                        ->title('Naturalezas Corregidas')
                                        ->body("Se corrigieron {$incorrectas} cuentas exitosamente en todos los equipos")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Validación Completada')
                                        ->body('Todas las cuentas tienen la naturaleza correcta en todos los equipos')
                                        ->success()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Actions\Action::make('Consolidar Cuentas Duplicadas')
                        ->icon('fas-object-group')
                        ->requiresConfirmation()
                        ->modalHeading('Consolidar Cuentas Duplicadas por Nombre')
                        ->modalDescription('Este proceso consolidará las cuentas contables de TODOS LOS EQUIPOS que tienen el mismo nombre (deudores/acreedores). Los movimientos de las cuentas duplicadas se transferirán a la cuenta principal y las cuentas duplicadas serán eliminadas.')
                        ->modalSubmitActionLabel('Consolidar Todos')
                        ->action(function (){
                            try {
                                // Ejecutar para todos los teams (sin parámetro --team-id)
                                Artisan::call('cuentas:consolidar-duplicadas-nombre', [
                                    '--no-interaction' => true
                                ]);

                                $output = Artisan::output();

                                // Mostrar el output completo en la notificación
                                Notification::make()
                                    ->title('Consolidación Completada')
                                    ->body('El proceso de consolidación ha finalizado para todos los equipos. Revisa los logs para más detalles.')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Actions\Action::make('Corregir Pendiente Pago PPD')
                        ->icon('fas-money-bill-wave')
                        ->requiresConfirmation()
                        ->modalHeading('Corregir campo pendiente_pago en facturas PPD')
                        ->modalDescription('Esta acción actualizará el campo pendiente_pago en todas las facturas PPD timbradas que no tengan complemento de pago aplicado. El campo se llenará con el total de la factura (aplicable solo al team actual).')
                        ->modalSubmitActionLabel('Corregir Pendientes')
                        ->action(function(){
                            // Buscar facturas PPD timbradas sin complemento o con pendiente_pago incorrecto
                            $facturas = \App\Models\Facturas::where('estado', 'Timbrada')
                                ->where('forma', 'PPD')
                                ->where('team_id', Filament::getTenant()->id)
                                ->get();

                            $corregidas = 0;
                            $yaCorrectas = 0;

                            foreach ($facturas as $factura) {
                                // Verificar si tiene complemento de pago
                                $tieneComplemento = \App\Models\ParPagos::where('uuidrel', $factura->uuid)
                                    ->where('team_id', $factura->team_id)
                                    ->exists();

                                // Si NO tiene complemento y pendiente_pago no es igual al total
                                if (!$tieneComplemento) {
                                    $totalFactura = floatval($factura->total) * floatval($factura->tcambio ?? 1);
                                    $pendienteActual = floatval($factura->pendiente_pago ?? 0);

                                    // Si el pendiente no coincide con el total (con tolerancia de 0.10)
                                    if (abs($pendienteActual - $totalFactura) > 0.10) {
                                        $factura->pendiente_pago = $totalFactura;
                                        $factura->save();
                                        $corregidas++;
                                    } else {
                                        $yaCorrectas++;
                                    }
                                } else {
                                    $yaCorrectas++;
                                }
                            }

                            Notification::make()
                                ->title('Proceso Completado')
                                ->body("{$corregidas} facturas corregidas | {$yaCorrectas} facturas ya estaban correctas")
                                ->success()
                                ->duration(5000)
                                ->send();
                        })
                ])
            ]);
    }

    private function asegurarCuentasCierre(int $teamId): void
    {
        CatCuentas::updateOrCreate(
            ['team_id' => $teamId, 'codigo' => '30400000'],
            [
                'nombre' => 'Cierre del Ejercicio Acumulativa',
                'acumula' => '0',
                'tipo' => 'A',
                'naturaleza' => 'A',
            ]
        );

        CatCuentas::updateOrCreate(
            ['team_id' => $teamId, 'codigo' => '30401000'],
            [
                'nombre' => 'Cierre del Ejercicio Detalle',
                'acumula' => '30400000',
                'tipo' => 'D',
                'naturaleza' => 'A',
            ]
        );
    }

    private function recontabilizarSaldos(int $teamId, ?int $ejercicio, ?int $periodo): array
    {
        $periodosAProcesar = $this->obtenerPeriodosAProcesar($teamId, $ejercicio, $periodo);
        if (empty($periodosAProcesar)) {
            return [
                'periodos' => 0,
                'cuentas' => 0,
                'errores' => 0,
            ];
        }

        $cuentasActualizadas = 0;
        $errores = 0;

        DB::beginTransaction();
        try {
            DB::table('cat_cuentas')
                ->where('team_id', $teamId)
                ->where(function ($q) {
                    $q->where('acumula', '10500')
                        ->orWhere('acumula', '10501');
                })
                ->update(['acumula' => '10501000']);

            DB::table('cat_cuentas')
                ->where('team_id', $teamId)
                ->where(function ($q) {
                    $q->where('acumula', '20100')
                        ->orWhere('acumula', '20101');
                })
                ->update(['acumula' => '20101000']);

            foreach ($periodosAProcesar as $periodoData) {
                $ejProc = $periodoData->ejercicio;
                $perProc = $periodoData->periodo;

                $cuentasAfectadas = DB::table('auxiliares')
                    ->select('codigo')
                    ->where('team_id', $teamId)
                    ->where('a_ejercicio', $ejProc)
                    ->where('a_periodo', $perProc)
                    ->distinct()
                    ->get();

                foreach ($cuentasAfectadas as $cuenta) {
                    try {
                        DB::table('saldos_reportes')
                            ->where('team_id', $teamId)
                            ->where('codigo', $cuenta->codigo)
                            ->where('ejercicio', $ejProc)
                            ->where('periodo', $perProc)
                            ->delete();

                        $this->recalcularCuenta($teamId, $cuenta->codigo, $ejProc, $perProc);
                        $cuentasActualizadas++;
                    } catch (\Exception $e) {
                        $errores++;
                        Log::error('Error al recalcular cuenta (Reparar Empresa)', [
                            'team_id' => $teamId,
                            'codigo' => $cuenta->codigo,
                            'ejercicio' => $ejProc,
                            'periodo' => $perProc,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            DB::commit();

            $this->recalcularJerarquiaCuentas($teamId);

            SaldosCache::invalidate($teamId);
            try {
                cache()->tags(['saldos'])->flush();
            } catch (\BadMethodCallException $e) {
                // El driver actual no soporta tags, ignorar
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'periodos' => count($periodosAProcesar),
            'cuentas' => $cuentasActualizadas,
            'errores' => $errores,
        ];
    }

    private function obtenerPeriodosAProcesar(int $teamId, ?int $ejercicio, ?int $periodo): array
    {
        $query = DB::table('auxiliares')
            ->select('a_ejercicio as ejercicio', 'a_periodo as periodo')
            ->where('team_id', $teamId)
            ->distinct();

        if ($ejercicio) {
            $query->where('a_ejercicio', $ejercicio);
        }

        if ($periodo) {
            $query->where('a_periodo', $periodo);
        }

        return $query->orderBy('ejercicio')->orderBy('periodo')->get()->toArray();
    }

    private function recalcularCuenta(int $teamId, string $codigo, int $ejercicio, int $periodo): void
    {
        $catCuenta = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->select('nombre', 'acumula', 'naturaleza')
            ->first();

        if (! $catCuenta) {
            $catCuenta = (object) [
                'nombre' => $codigo,
                'acumula' => 'S',
                'naturaleza' => 'D',
            ];
        }

        $nivel = substr_count($codigo, '.') + 1;

        $saldoAnterior = DB::table('auxiliares')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', '<', $periodo)
            ->selectRaw('COALESCE(SUM(cargo - abono), 0) as saldo')
            ->value('saldo') ?? 0;

        $movimientos = DB::table('auxiliares')
            ->where('team_id', $teamId)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', $periodo)
            ->selectRaw('
                COALESCE(SUM(cargo), 0) as cargos,
                COALESCE(SUM(abono), 0) as abonos
            ')
            ->first();

        $cargos = $movimientos->cargos ?? 0;
        $abonos = $movimientos->abonos ?? 0;
        $saldoFinal = $saldoAnterior + $cargos - $abonos;

        DB::table('saldos_reportes')->updateOrInsert(
            [
                'team_id' => $teamId,
                'codigo' => $codigo,
            ],
            [
                'cuenta' => $catCuenta->nombre,
                'acumula' => $catCuenta->acumula,
                'naturaleza' => $catCuenta->naturaleza,
                'nivel' => $nivel,
                'anterior' => $saldoAnterior,
                'cargos' => $cargos,
                'abonos' => $abonos,
                'final' => $saldoFinal,
                'updated_at' => now(),
            ]
        );
    }

    private function recalcularJerarquiaCuentas(int $teamId): void
    {
        $cuentasPadre = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where('acumula', '!=', 'N')
            ->whereRaw('LENGTH(codigo) - LENGTH(REPLACE(codigo, ".", "")) < 2')
            ->orderBy('codigo')
            ->get();

        foreach ($cuentasPadre as $padre) {
            $saldoHijas = DB::table('saldos_reportes')
                ->where('team_id', $teamId)
                ->where('codigo', 'LIKE', $padre->codigo . '.%')
                ->where('nivel', '>', substr_count($padre->codigo, '.') + 1)
                ->selectRaw('
                    COALESCE(SUM(anterior), 0) as total_anterior,
                    COALESCE(SUM(cargos), 0) as total_cargos,
                    COALESCE(SUM(abonos), 0) as total_abonos,
                    COALESCE(SUM(final), 0) as total_final
                ')
                ->first();

            if ($saldoHijas) {
                DB::table('saldos_reportes')->updateOrInsert(
                    [
                        'team_id' => $teamId,
                        'codigo' => $padre->codigo,
                    ],
                    [
                        'cuenta' => $padre->nombre,
                        'acumula' => $padre->acumula,
                        'naturaleza' => $padre->naturaleza,
                        'nivel' => substr_count($padre->codigo, '.') + 1,
                        'anterior' => $saldoHijas->total_anterior,
                        'cargos' => $saldoHijas->total_cargos,
                        'abonos' => $saldoHijas->total_abonos,
                        'final' => $saldoHijas->total_final,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
