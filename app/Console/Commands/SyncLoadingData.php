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
                            {--month= : Bulan spesifik (format: Y-m)}';

    protected $description = 'Sinkronisasi data loading mesin dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly AccessDatabaseServiceInterface $accessService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $correlationId = uniqid('sync_loading_', true);
        $date  = $this->option('date')  ?: null;
        $month = $this->option('month') ?: null;

        Log::info('SyncLoadingData: Command dimulai', [
            'correlationId' => $correlationId,
            'operation' => 'sync_loading_data',
            'date'  => $date  ?? 'all',
            'month' => $month ?? '-',
        ]);

        $this->info('Memulai sinkronisasi data loading mesin dari Access...');

        // Fetch data dari Access
        $records = $this->accessService->fetchLoadingRecords($date, $month);

        if (empty($records)) {
            $message = 'Tidak ada data yang ditemukan di Access (atau koneksi gagal).';
            $this->warn($message);

            Log::warning('SyncLoadingData: '.$message, [
                'correlationId' => $correlationId,
                'operation' => 'sync_loading_data',
            ]);

            return self::SUCCESS;
        }

        $this->info('Ditemukan '.count($records).' record dari Access. Mulai upsert ke MySQL...');

        $synced = 0;
        $errors = 0;

        foreach ($records as $record) {
            try {
                $this->upsertRecord($record);
                $synced++;
            } catch (\Throwable $e) {
                $errors++;

                Log::error('SyncLoadingData: Gagal upsert record', [
                    'correlationId' => $correlationId,
                    'operation' => 'upsert_loading_record',
                    'error' => $e->getMessage(),
                    'machine_no' => $record['Machine No'] ?? '?',
                    'date' => $record['Date'] ?? '?',
                ]);
            }
        }

        $this->info("Sinkronisasi selesai: {$synced} record berhasil, {$errors} error.");

        Log::info('SyncLoadingData: Command selesai', [
            'correlationId' => $correlationId,
            'operation' => 'sync_loading_data',
            'synced' => $synced,
            'errors' => $errors,
            'total'  => count($records),
        ]);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Upsert satu record ke tabel loading_machine_records.
     * Mapping nama kolom Access → MySQL.
     *
     * @param  array<string, mixed>  $record
     */
    private function upsertRecord(array $record): void
    {
        $machineNo = trim((string) ($record['Machine No'] ?? ''));
        $rawDate   = $record['Date'] ?? null;

        // Kedua field wajib ada
        if ($machineNo === '' || $rawDate === null) {
            return;
        }

        $date = date('Y-m-d', strtotime((string) $rawDate));

        // Kolom numerik dari Access — kadang berformat HH:MM atau string
        $data = [
            'machine_no'           => $machineNo,
            'date'                 => $date,
            'work_time_mc'         => $this->parseNumeric($record['Work Time Mc'] ?? null),
            'durasi_m_total'       => $this->parseNumeric($record['SumOfDurasi M Total'] ?? null),
            'work_time_eff_m'      => $this->parseNumeric($record['SumOfWork Time Eff M'] ?? null),
            'loading_pct'          => $this->parseNumeric($record['% Loading'] ?? null),
            'machine_type_process' => $this->parseString($record['Machine Type Process'] ?? null),
            'mesin_type'           => $this->parseString($record['Mesin Type'] ?? null),
        ];

        $uniqueKeys = ['machine_no', 'date'];

        try {
            LoadingMachineRecord::updateOrCreate(
                array_intersect_key($data, array_flip($uniqueKeys)),
                $data
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition duplicate entry — fallback ke update langsung
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), '1062')) {
                LoadingMachineRecord::where('machine_no', $data['machine_no'])
                    ->where('date', $data['date'])
                    ->update($data);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Parse nilai numerik dari Access (bisa HH:MM, string kosong, atau angka biasa).
     */
    private function parseNumeric(mixed $val): ?float
    {
        if ($val === null || $val === '' || $val === '-' || $val === ':') {
            return null;
        }

        $str = trim((string) $val);

        // Format HH:MM → konversi ke desimal
        if (str_contains($str, ':')) {
            $parts = explode(':', $str);
            if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return (float) $parts[0] + ((float) $parts[1] / 60);
            }

            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $str);

        return is_numeric($clean) ? (float) $clean : null;
    }

    /**
     * Parse nilai string dari Access, kembalikan null jika kosong.
     */
    private function parseString(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        $str = trim((string) $val);

        return $str === '' || $str === '-' ? null : $str;
    }
}
