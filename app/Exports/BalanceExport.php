<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class BalanceExport implements FromView
{
    use Exportable;
    public function __construct(int $empresa, int $periodo, int $ejercicio)
    {
        $this->empresa = $empresa;
        $this->periodo = $periodo;
        $this->ejercicio = $ejercicio;
    }

    public function view(): View
    {
        return view('BalanceNewExport',['empresa'=>$this->empresa,'periodo'=>$this->periodo,'ejercicio'=>$this->ejercicio]);
    }
}
