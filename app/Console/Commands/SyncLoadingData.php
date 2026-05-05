<?php

namespace App\Console\Commands;

use App\Models\LoadingMachineRecord;
use App\Services\AccessDatabaseServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command untuk sinkronisasi data loading mesin dari Microsoft Access ke MySQL.
 * Dapat dijalankan manual: php artisan sync:loading-data
 * Atau otomatis via scheduler.
 */
class SyncLoadingData extends Command
{
    protected $signature = 'sync:loading-data
                            {--date=  : Tanggal spesifik (format: Y-m-d)}
                            {--month= : Bulan spesifik (format: Y-m)}
                            {--queue : Jalankan di background (queue)}';

    protected $description = 'Sinkronisasi data loading mesin dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly \App\Services\AccessSyncService $syncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date  = $this->option('date')  ?: null;
        $month = $this->option('month') ?: null;

        if ($this->option('queue')) {
            $this->info('Dispatching loading sync job to queue...');
            \App\Jobs\SyncLoadingDataJob::dispatch($date, $month);
            return self::SUCCESS;
        }

        $this->info('Memulai sinkronisasi data loading (Synchronous)...');
        
        $result = $this->syncService->syncLoading($date, $month);

        if ($result['status'] === 'no_data' || $result['status'] === 'no_valid_data') {
            $this->warn('Tidak ada data loading valid untuk di-sinkronisasi.');
        } else {
            $this->info("Sinkronisasi loading selesai: {$result['synced']} record berhasil di-upsert.");
        }

        return self::SUCCESS;
    }
}
