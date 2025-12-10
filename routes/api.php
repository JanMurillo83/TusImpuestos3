<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/auxvencpto', [\App\Http\Controllers\MainChartsController::class, 'GeneraAbonos_Aux_Detalle'])->name('auxvencpto');
Route::get('/auxvencptoan', [\App\Http\Controllers\MainChartsController::class, 'GeneraAbonos_Aux_Detalle_an'])->name('auxvencptoan');
Route::get( '/ctascobrar', [\App\Http\Controllers\MainChartsController::class, 'CuentasCobrar'])->name('ctascobrar');
Route::get( '/ctascobrardet', [\App\Http\Controllers\MainChartsController::class, 'CuentasCobrar_detalle'])->name('ctascobrardet');
Route::get( '/ctaspagar', [\App\Http\Controllers\MainChartsController::class, 'CuentasPagar'])->name('ctaspagar');
Route::get( '/ctaspagardet', [\App\Http\Controllers\MainChartsController::class, 'CuentasPagar_detalle'])->name('ctaspagardet');
