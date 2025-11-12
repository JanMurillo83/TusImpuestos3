<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;

class RepContables extends Controller
{
    public function auxiliares($periodo,$ejercicio,$empresa)
    {
        $pdf = PDF::loadView('AuxiliaresPeriodo', array('empresa'=>$empresa,'periodo'=>$periodo,'ejercicio'=>$ejercicio))
        ->setPaper('letter', 'portrait');

        return $pdf->download('Auxiliar-'.$periodo.'-'.$ejercicio.'.pdf');
    }
}
