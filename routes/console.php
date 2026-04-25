<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Task Scheduling: Sinkronisasi Access → MySQL
|--------------------------------------------------------------------------
| Dijalankan setiap 5 menit, tanpa overlap untuk mencegah race condition.
| Output di-append ke file log agar bisa di-review.
*/
Schedule::command('sync:access-data')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-access.log'));

Schedule::command('sync:loading-data')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-loading.log'));

