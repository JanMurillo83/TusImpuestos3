<?php

namespace App\Filament\Clusters\ContReportes\Pages;

use App\Filament\Clusters\ContReportes;
use App\Http\Controllers\ReportesController;
use App\Models\archivos_pdf;
use App\Models\Listareportes;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Actions\Action as ActionsAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Storage;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;

use function Pest\Laravel\call;

class ReportesPag extends Page implements HasInfolists
{
    use InteractsWithInfolists;
    protected static ?string $title = 'Contabilidad';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.cont-reportes.pages.reportes-pag';
    protected static ?string $cluster = ContReportes::class;
    public static bool $shouldRegisterNavigation = false;

    public ?string $ruta_archivo = '';
    public ?string $leyenda_archivo = '';

    /*public function getHeaderActions(): array
    {
        return [
            Action::make('balanza')
            ->label('Balanza de Comprobacion')
            ->form([
                Select::make('ejercicio')
                ->options(function()
                {
                    $ant = Filament::getTenant()->ejercicio;
                    $f = $ant - 10;
                    $t = $ant + 11;
                    $pers = [];
                    for($i=$f;$i<$t;$i++)
                    {
                        $pers[$i] =$i;
                    }
                    return $pers;
                }
                )->default(Filament::getTenant()->ejercicio),
                Select::make('periodo')
                ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12]
                )->default(Filament::getTenant()->periodo),
            ])
            ->action(function($data,$livewire){
                $request = new \Illuminate\Http\Request();
                $per = $data['periodo'] ?? Filament::getTenant()->periodo;
                $eje = $data['ejercicio'] ?? Filament::getTenant()->ejercicio;
                $request->replace(['month'=>$per,'year'=>$eje]);
                $reporte = new ReportesController;
                $resp = $reporte->balanza($request);
                if($resp != 'Error')
                {
                    $this->ruta_archivo = $resp;
                    $this->leyenda_archivo = 'Balanza de Comprobación';
                }
            }),
            Action::make('balance')
            ->label('Balance General')
            ->form([
                Select::make('ejercicio')
                ->options(function()
                {
                    $ant = Filament::getTenant()->ejercicio;
                    $f = $ant - 10;
                    $t = $ant + 11;
                    $pers = [];
                    for($i=$f;$i<$t;$i++)
                    {
                        $pers[$i] =$i;
                    }
                    return $pers;
                }
                )->default(Filament::getTenant()->ejercicio),
                Select::make('periodo')
                ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12]
                )->default(Filament::getTenant()->periodo),
            ])
            ->action(function($data,$livewire){
                $request = new \Illuminate\Http\Request();
                $per = $data['periodo'] ?? Filament::getTenant()->periodo;
                $eje = $data['ejercicio'] ?? Filament::getTenant()->ejercicio;
                $request->replace(['month'=>$per,'year'=>$eje]);
                $reporte = new ReportesController;
                $resp = $reporte->balancegral($request);
                if($resp != 'Error')
                {
                    $this->ruta_archivo = $resp;
                    $this->leyenda_archivo = 'Balance General';
                }
            }),
            Action::make('edore')
            ->label('Estado de Resultados')
            ->form([
                Select::make('ejercicio')
                ->options(function()
                {
                    $ant = Filament::getTenant()->ejercicio;
                    $f = $ant - 10;
                    $t = $ant + 11;
                    $pers = [];
                    for($i=$f;$i<$t;$i++)
                    {
                        $pers[$i] =$i;
                    }
                    return $pers;
                }
                )->default(Filament::getTenant()->ejercicio),
                Select::make('periodo')
                ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12]
                )->default(Filament::getTenant()->periodo),
            ])
            ->action(function($data,$livewire){
                $request = new \Illuminate\Http\Request();
                $per = $data['periodo'] ?? Filament::getTenant()->periodo;
                $eje = $data['ejercicio'] ?? Filament::getTenant()->ejercicio;
                $request->replace(['month'=>$per,'year'=>$eje]);
                $reporte = new ReportesController;
                $resp = $reporte->edores($request);
                if($resp != 'Error')
                {
                    $this->ruta_archivo = $resp;
                    $this->leyenda_archivo = 'Estado de Resultados';
                }
            })
        ];
    }*/

    /*public function infolist(Infolist $infolist): Infolist
    {
    return $infolist
    ->state([
        'url'=>$this->ruta_archivo,
        'dato'=>$this->leyenda_archivo,
        //'leyenda'=>$this->leyenda_archivo
    ])
        ->schema([

            TextEntry::make('dato')
                ->label(fn($state) => $state->dato ?? ''),
            PdfViewerEntry::make('url')
                ->label('')
                ->minHeight('60svh')
            //->fileUrl()
        ]);
    }*/
    public function form(Form $form): Form
    {
        return $form
        ->model(Listareportes::class)
        ->schema([
            Section::make('Reportes Contables')
            ->schema([
                Select::make('Reporte')
                ->reactive()
                ->options([
                    'Balanza'=>'Balanza de Comprobacion',
                    'Balance'=>'Balance General',
                    'Estado'=>'Estado de Resultados',
                    'Diario'=>'Diario de Polizas',
                ])
            ]),
            Section::make('Filtro')
            ->schema([
                Select::make('Periodo'),
                Select::make('Ejercicio'),
                Toggle::make('ConS')->label('Solo con Saldos'),
            ])->columns(3)->hidden(fn (Get $get) => $get('Reporte') === null)
        ]);
    }
}


