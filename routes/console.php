<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bersihkan file temp PDF merge yang tertinggal setiap hari pukul 02:00
Schedule::command('pdf-merge:clean-temp --hours=12')
    ->dailyAt('02:00')
    ->withoutOverlapping();
