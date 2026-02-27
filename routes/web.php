<?php

use Illuminate\Support\Facades\Route;

/*Route::group(['middleware' => ['web']], function () {
    //Route::get('/', function () { return view('views/login');});
    Route::get('/', function () { return redirect('main');});
});*/
Route::get('/reportes/contabilidad/balanza', function () { return view('ContaRep/Balanza');});
Route::get('/reportes/contabilidad/balance', function () { return view('ContaRep/Balance');});
Route::get('/reportes/contabilidad/estado', function () { return view('ContaRep/Estado');});

// Reportes NIF (Normas de Información Financiera)
Route::get('/reportes/nif/balanza-comprobacion', [\App\Http\Controllers\ReportesNIFController::class, 'balanzaComprobacion'])->name('reportes.nif.balanza');
Route::get('/reportes/nif/balanza-comprobacion-excel', [\App\Http\Controllers\ReportesNIFController::class, 'balanzaComprobacionExcel'])->name('reportes.nif.balanza.excel');
Route::get('/reportes/nif/libro-mayor', [\App\Http\Controllers\ReportesNIFController::class, 'libroMayor'])->name('reportes.nif.libro-mayor');
Route::get('/reportes/nif/auxiliares', [\App\Http\Controllers\ReportesNIFController::class, 'auxiliaresReporte'])->name('reportes.nif.auxiliares');
Route::get('/reportes/nif/diario-general', [\App\Http\Controllers\ReportesNIFController::class, 'diarioGeneral'])->name('reportes.nif.diario');
Route::get('/reportes/nif/polizas-descuadradas', [\App\Http\Controllers\ReportesNIFController::class, 'polizasDescuadradas'])->name('reportes.nif.descuadradas');
Route::get('/reportes/nif/balance-comparativo', [\App\Http\Controllers\ReportesNIFController::class, 'balanceGeneralComparativo'])->name('reportes.nif.balance-comparativo');
Route::get('/reportes/nif/estado-resultados-comparativo', [\App\Http\Controllers\ReportesNIFController::class, 'estadoResultadosComparativo'])->name('reportes.nif.resultados-comparativo');
Route::get('/reportes/nif/antiguedad-saldos', [\App\Http\Controllers\ReportesNIFController::class, 'antiguedadSaldos'])->name('reportes.nif.antiguedad');
Route::get('/reportes/nif/razones-financieras', [\App\Http\Controllers\ReportesNIFController::class, 'razonesFinancieras'])->name('reportes.nif.razones');
Route::get('/reportes/nif/reporte-iva', [\App\Http\Controllers\ReportesNIFController::class, 'reporteIVA'])->name('reportes.nif.iva');
Route::get('/reportes/nif/reporte-diot', [\App\Http\Controllers\ReportesNIFController::class, 'reporteDIOT'])->name('reportes.nif.diot');
Route::get('/reportes/nif/reporte-retenciones', [\App\Http\Controllers\ReportesNIFController::class, 'reporteRetenciones'])->name('reportes.nif.retenciones');
Route::get('/reportes/nif/balance-general', [\App\Http\Controllers\ReportesNIFController::class, 'balanceGeneralNIF'])->name('reportes.nif.balance');
Route::get('/reportes/nif/estado-resultados', [\App\Http\Controllers\ReportesNIFController::class, 'estadoResultadosNIF'])->name('reportes.nif.resultados');
Route::get('/reportes/nif/cambios-capital', [\App\Http\Controllers\ReportesNIFController::class, 'estadoCambiosCapitalNIF'])->name('reportes.nif.capital');
Route::get('/reportes/nif/flujo-efectivo', [\App\Http\Controllers\ReportesNIFController::class, 'estadoFlujoEfectivoNIF'])->name('reportes.nif.flujo');
Route::get('/reportes/nif/exportar-todos-excel', [\App\Http\Controllers\ReportesNIFController::class, 'exportarTodosExcel'])->name('reportes.nif.todos.excel');
Route::get('/mainview',[\App\Http\Controllers\MainChartsController::class,'mainview'])->name('mainviewns');
Route::prefix('{tenantSlug}')->group(function () {
    Route::get('/tiadmin', function (string $tenantSlug) {
        return redirect("/{$tenantSlug}/tiadmin/comercial");
    });
    Route::middleware('auth')->group(function () {
        Route::get('/tiadmin/comercial-embed', [\App\Http\Controllers\ComercialDashboardController::class, 'index'])
            ->name('tiadmin.comercial.embed');
        Route::get('/tiadmin/comercial/api/bootstrap', [\App\Http\Controllers\ComercialDashboardController::class, 'bootstrap'])
            ->name('tiadmin.comercial.bootstrap');
        Route::post('/tiadmin/comercial/api/quotes', [\App\Http\Controllers\ComercialDashboardController::class, 'createQuote'])
            ->name('tiadmin.comercial.quotes.create');
        Route::put('/tiadmin/comercial/api/quotes/{quote}', [\App\Http\Controllers\ComercialDashboardController::class, 'updateQuote'])
            ->name('tiadmin.comercial.quotes.update');
        Route::post('/tiadmin/comercial/api/quotes/{quote}/activities', [\App\Http\Controllers\ComercialDashboardController::class, 'addActivity'])
            ->name('tiadmin.comercial.quotes.activities');
        Route::post('/tiadmin/comercial/api/invoices', [\App\Http\Controllers\ComercialDashboardController::class, 'createInvoice'])
            ->name('tiadmin.comercial.invoices.create');
        Route::get('/tiadmin/descarga', [\App\Http\Controllers\TiadminDownloadController::class, 'download'])
            ->name('tiadmin.download');
        Route::get('/tiadmin/descarga/redirect', [\App\Http\Controllers\TiadminDownloadController::class, 'downloadAndRedirect'])
            ->name('tiadmin.download.redirect');
    });
    Route::get('/contabilizar', [\App\Http\Controllers\ReportesController::class, 'ContabilizaReporte_ret'])->name('contabilizar');
    Route::get('/grafica1', [\App\Http\Controllers\ChartsController::class, 'showChart1'])->name('showChart1');
    Route::get('/grafica2', [\App\Http\Controllers\ChartsController::class, 'showChart2'])->name('showChart2');
    Route::get('/grafica3', [\App\Http\Controllers\ChartsController::class, 'showChart3'])->name('showChart3');
    Route::get('/grafica4', [\App\Http\Controllers\ChartsController::class, 'showChart4'])->name('showChart4');
    Route::get('/grafica5', [\App\Http\Controllers\ChartsController::class, 'showChart5'])->name('showChart5');
    Route::get('/grafica6', [\App\Http\Controllers\ChartsController::class, 'showChart6'])->name('showChart6');
    Route::get('/grafica7', [\App\Http\Controllers\ChartsController::class, 'showChart7'])->name('showChart7');
    Route::get('/grafica8', [\App\Http\Controllers\ChartsController::class, 'showChart8'])->name('showChart8');
    Route::get('/mainview/{team_id}',[\App\Http\Controllers\MainChartsController::class,'mainview'])->name('mainview');

});
