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
| 1. Rutin (Setiap 5 menit): Sinkronisasi rolling window 7 hari terakhir.
|    Menjamin data yang diinput terlambat (kemarin/lusa/minggu ini) selalu ter-update otomatis.
| 2. Malam (Setiap hari pukul 01:00): Sinkronisasi 30 hari terakhir.
| 3. Mingguan (Setiap Minggu pukul 02:00): Sinkronisasi penuh semua data historis.
*/

// Rolling 7 hari (setiap 5 menit)
Schedule::command('sync:access-data --days=7')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-access.log'));

Schedule::command('sync:loading-data --days=7')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-loading.log'));

// Deep sync 30 hari (setiap malam pukul 01:00)
Schedule::command('sync:access-data --days=30')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-access-nightly.log'));

Schedule::command('sync:loading-data --days=30')
    ->dailyAt('01:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-loading-nightly.log'));

// Full historical sync (setiap Minggu pukul 02:00)
Schedule::command('sync:access-data --all')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-access-weekly.log'));

Schedule::command('sync:loading-data --all')
    ->weeklyOn(0, '02:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-loading-weekly.log'));


