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
                            {--days=7 : Rentang hari ke belakang untuk sinkronisasi (default: 7 hari)}
                            {--all : Sinkronisasi semua data historis (tanpa filter tanggal)}
                            {--queue : Jalankan di background (queue)}';

    protected $description = 'Sinkronisasi data loading mesin dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly \App\Services\AccessSyncService $syncService,
        private readonly \App\Services\AccessDatabaseServiceInterface $accessDb,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date  = $this->option('date')  ?: null;
        $month = $this->option('month') ?: null;
        $days  = null;

        // Priority: --all > --date > --month > --days (default 7 days)
        if ($this->option('all')) {
            $date = null;
            $month = null;
            $days = null;
            $this->comment('Sinkronisasi semua data loading historis (tanpa filter tanggal).');
        } elseif ($date !== null) {
            $this->comment("Sinkronisasi data loading untuk tanggal spesifik: $date");
        } elseif ($month !== null) {
            $this->comment("Sinkronisasi data loading untuk bulan: $month");
        } else {
            $days = (int) ($this->option('days') ?: 7);
            $this->comment("Sinkronisasi loading rolling window $days hari terakhir (termasuk hari ini).");
        }

        if ($this->option('queue')) {
            $this->info('Dispatching loading sync job to queue...');
            \App\Jobs\SyncLoadingDataJob::dispatch($date, $month);
            return self::SUCCESS;
        }

        $this->info('Mengecek koneksi ke database Access...');
        if (!$this->accessDb->testConnection()) {
            $this->error('Gagal terhubung ke database Microsoft Access.');
            $this->error('Detail: ' . $this->accessDb->getLastError());
            return self::FAILURE;
        }

        $infoScope = $date ? "Tanggal: $date" : ($month ? "Bulan: $month" : ($days ? "$days Hari Terakhir" : 'Semua Data'));
        $this->info("Memulai sinkronisasi data loading ($infoScope)...");
        
        $result = $this->syncService->syncLoading($date, $month, $days);

        if ($result['status'] === 'no_data') {
            $this->warn('Tidak ada data loading ditemukan di database Access.');
        } elseif ($result['status'] === 'no_valid_data') {
            $this->warn('Data loading ditemukan, tetapi kolom wajib (Machine No / Date) kosong atau tidak cocok.');
        } else {
            $this->info("Sinkronisasi loading selesai: {$result['synced']} record berhasil di-upsert.");
        }

        return self::SUCCESS;
    }
}
