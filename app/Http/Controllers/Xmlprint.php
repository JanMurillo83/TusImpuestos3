<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vsmoraes\Pdf\Pdf;

class Xmlprint extends Controller
{
    private $pdf;

    public function imprimirpdf($xml)
    {
        return $this->pdf
            ->load($xml)
            ->show();
    }
}
