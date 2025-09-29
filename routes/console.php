<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programa la emisión automática de facturas modelo diariamente a las 06:00
Schedule::command('facturas:modelos:emitir-debidas')->dailyAt('06:00');
