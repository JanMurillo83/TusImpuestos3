<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpleadosController;

Route::group(['middleware' => ['web']], function () {
    //Route::get('/', function () { return view('views/login');});
    Route::get('/', function () { return redirect('main');});
});
