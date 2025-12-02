<?php

use Illuminate\Support\Facades\Route;

/*Route::group(['middleware' => ['web']], function () {
    //Route::get('/', function () { return view('views/login');});
    Route::get('/', function () { return redirect('main');});
});*/
Route::get('/reportes/contabilidad/balanza', function () { return view('ContaRep/Balanza');});
Route::get('/reportes/contabilidad/balance', function () { return view('ContaRep/Balance');});
Route::get('/reportes/contabilidad/estado', function () { return view('ContaRep/Estado');});
Route::prefix('{tenantSlug}')->group(function () {
    Route::get('/contabilizar', [\App\Http\Controllers\ReportesController::class, 'ContabilizaReporte_ret'])->name('contabilizar');
    Route::get('/grafica1', [\App\Http\Controllers\ChartsController::class, 'showChart1'])->name('showChart1');
    Route::get('/grafica2', [\App\Http\Controllers\ChartsController::class, 'showChart2'])->name('showChart2');
    Route::get('/grafica3', [\App\Http\Controllers\ChartsController::class, 'showChart3'])->name('showChart3');
    Route::get('/grafica4', [\App\Http\Controllers\ChartsController::class, 'showChart4'])->name('showChart4');
    Route::get('/grafica5', [\App\Http\Controllers\ChartsController::class, 'showChart5'])->name('showChart5');
    Route::get('/grafica6', [\App\Http\Controllers\ChartsController::class, 'showChart6'])->name('showChart6');
    Route::get('/grafica7', [\App\Http\Controllers\ChartsController::class, 'showChart7'])->name('showChart7');
    Route::get('/grafica8', [\App\Http\Controllers\ChartsController::class, 'showChart8'])->name('showChart8');
});
