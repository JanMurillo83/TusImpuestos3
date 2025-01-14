<?php

use Illuminate\Support\Facades\Route;

/*Route::group(['middleware' => ['web']], function () {
    //Route::get('/', function () { return view('views/login');});
    Route::get('/', function () { return redirect('main');});
});*/
Route::get('/reportes/contabilidad/balanza', function () { return view('ContaRep/Balanza');});
Route::get('/reportes/contabilidad/balance', function () { return view('ContaRep/Balance');});
Route::get('/reportes/contabilidad/estado', function () { return view('ContaRep/Estado');});
