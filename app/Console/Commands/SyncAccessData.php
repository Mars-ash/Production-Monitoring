<?php

namespace App\Console\Commands;

use App\Models\ProductionRecord;
use App\Services\AccessDatabaseServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAccessData extends Command
{
    protected $signature = 'sync:access-data
                            {--date=  : Tanggal spesifik (format: Y-m-d)}
                            {--days=7 : Rentang hari ke belakang untuk sinkronisasi (default: 7 hari)}
                            {--all : Sinkronisasi semua data historis (tanpa filter tanggal)}
                            {--queue : Jalankan di background (queue)}';

    protected $description = 'Sinkronisasi data produksi dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly \App\Services\AccessSyncService $syncService,
        private readonly \App\Services\AccessDatabaseServiceInterface $accessDb,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date = $this->option('date') ?: null;
        $days = null;

        // Priority: --all > --date > --days (default 7 days)
        if ($this->option('all')) {
            $date = null;
            $days = null;
            $this->comment('Sinkronisasi semua data historis (tanpa filter tanggal).');
        } elseif ($date !== null) {
            $this->comment("Sinkronisasi data untuk tanggal spesifik: $date");
        } else {
            $days = (int) ($this->option('days') ?: 7);
            $this->comment("Sinkronisasi rolling window $days hari terakhir (termasuk hari ini).");
        }

        if ($this->option('queue')) {
            $this->info('Dispatching sync job to queue...');
            \App\Jobs\SyncAccessDataJob::dispatch($date);
            return self::SUCCESS;
        }

        $this->info('Mengecek koneksi ke database Access...');
        if (!$this->accessDb->testConnection()) {
            $this->error('Gagal terhubung ke database Microsoft Access.');
            $this->error('Detail: ' . $this->accessDb->getLastError());
            return self::FAILURE;
        }

        $infoScope = $date ? "Tanggal: $date" : ($days ? "$days Hari Terakhir" : 'Semua Data');
        $this->info("Memulai sinkronisasi data dari Access ($infoScope)...");
        
        $result = $this->syncService->sync($date, $days);

        if ($result['status'] === 'no_data') {
            $this->warn('Tidak ada data ditemukan di database Access untuk kriteria pencarian ini.');
        } elseif ($result['status'] === 'no_valid_data') {
            $this->warn('Data ditemukan di Access, tetapi tidak ada record yang valid untuk diproses (cek log untuk detail schema).');
        } else {
            $this->info("Sinkronisasi selesai: {$result['synced']} record berhasil di-upsert, {$result['deleted']} yatim dihapus.");
        }

        return self::SUCCESS;
    }
}
