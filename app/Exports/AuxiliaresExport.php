<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class AuxiliaresExport implements FromView
{
    use Exportable;
    public int $empresa;
    public int $periodo;
    public int $ejercicio;
    public string $cuenta_ini;
    public string $cuenta_fin;
    public string $mes_ini;
    public string $mes_fin;
    public function __construct(int $empresa,int $periodo, string $mes_ini,string $mes_fin, int $ejercicio, ?string $cuenta_ini = null, ?string $cuenta_fin = null)
    {
        $this->empresa = $empresa;
        $this->ejercicio = $ejercicio;
        $this->cuenta_ini = $cuenta_ini;
        $this->cuenta_fin = $cuenta_fin;
        $this->mes_ini = $mes_ini;
        $this->mes_fin = $mes_fin;
        $this->periodo = $periodo;
    }

    public function view(): View
    {
        return view('AuxiliaresPeriodo_Excel',[
            'empresa'=>$this->empresa,
            'ejercicio'=>$this->ejercicio,
            'cuenta_ini'=>$this->cuenta_ini,
            'cuenta_fin'=>$this->cuenta_fin,
            'mes_ini'=>$this->mes_ini,
            'mes_fin'=>$this->mes_fin,
            'periodo'=>$this->periodo
        ]);
    }
}
