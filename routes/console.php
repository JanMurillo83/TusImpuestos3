<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programa la emisión automática de facturas modelo diariamente a las 06:00
Schedule::command('facturas:modelos:emitir-debidas')->dailyAt('06:00');

// Enviar reportes semanales cada lunes a la hora configurada (por defecto 08:00)
Schedule::command('reports:send-weekly')
    ->weeklyOn(1, env('WEEKLY_REPORTS_TIME', '08:00'))
    ->withoutOverlapping();

// Descarga automática de CFDIs del SAT (sin restricción de hora)
Schedule::command('sat:descargar-automatico')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
