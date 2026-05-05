<?php

namespace App\Console\Commands;

use App\Models\ProductionRecord;
use App\Services\AccessDatabaseServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command untuk sinkronisasi data dari Microsoft Access ke MySQL.
 * Dapat dijalankan manual: php artisan sync:access-data
 * Atau otomatis via scheduler setiap 5 menit.
 */
class SyncAccessData extends Command
{
    protected $signature = 'sync:access-data
                            {--date=  : Tanggal spesifik (format: Y-m-d)}
                            {--queue : Jalankan di background (queue)}';

    protected $description = 'Sinkronisasi data produksi dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly \App\Services\AccessSyncService $syncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date = $this->option('date');

        if ($this->option('queue')) {
            $this->info('Dispatching sync job to queue...');
            \App\Jobs\SyncAccessDataJob::dispatch($date);
            return self::SUCCESS;
        }

        $this->info('Memulai sinkronisasi data dari Access (Synchronous)...');
        
        $result = $this->syncService->sync($date);

        if ($result['status'] === 'no_data' || $result['status'] === 'no_valid_data') {
            $this->warn('Tidak ada data valid untuk di-sinkronisasi.');
        } else {
            $this->info("Sinkronisasi selesai: {$result['synced']} record berhasil di-upsert, {$result['deleted']} yatim dihapus.");
        }

        return self::SUCCESS;
    }
}
