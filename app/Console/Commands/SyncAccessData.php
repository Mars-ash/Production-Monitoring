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
                            {--date= : Tanggal spesifik (format: Y-m-d)}';

    protected $description = 'Sinkronisasi data produksi dari Microsoft Access ke MySQL';

    public function __construct(
        private readonly AccessDatabaseServiceInterface $accessService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $correlationId = uniqid('sync_cmd_', true);
        $date = $this->option('date');

        Log::info('SyncAccessData: Command dimulai', [
            'correlationId' => $correlationId,
            'operation' => 'sync_access_data',
            'date' => $date ?? 'today',
        ]);

        $this->info('Memulai sinkronisasi data dari Access...');

        // Fetch data dari Access
        $records = $this->accessService->fetchRecords($date);

        if (empty($records)) {
            $message = 'Tidak ada data yang ditemukan di Access (atau koneksi gagal).';
            $this->warn($message);

            Log::warning('SyncAccessData: '.$message, [
                'correlationId' => $correlationId,
                'operation' => 'sync_access_data',
            ]);

            return self::SUCCESS;
        }

        $synced = 0;
        $errors = 0;
        $syncedIds = [];

        foreach ($records as $record) {
            try {
                $id = $this->upsertRecord($record);
                if ($id) {
                    $syncedIds[] = $id;
                }
                $synced++;
            } catch (\Throwable $e) {
                $errors++;

                Log::error('SyncAccessData: Gagal upsert record', [
                    'correlationId' => $correlationId,
                    'operation' => 'upsert_record',
                    'error' => $e->getMessage(),
                    'record' => array_intersect_key($record, array_flip([
                        'machine_no', 'date', 'part_no',
                    ])),
                ]);
            }
        }

        // Garbage collection: hapus record yatim di MySQL yang tidak ada di access
        $deletedCount = 0;
        if (!empty($syncedIds)) {
            if ($date) {
                // Hapus yatim hanya pada tanggal spesifik
                $deletedCount = ProductionRecord::where('date', $date)->whereNotIn('id', $syncedIds)->delete();
            } else {
                // Fetch All Time, hapus semua yatim
                $deletedCount = ProductionRecord::whereNotIn('id', $syncedIds)->delete();
            }
        }

        $this->info("Sinkronisasi selesai: {$synced} record berhasil, {$deletedCount} yatim dihapus, {$errors} error.");

        Log::info('SyncAccessData: Command selesai', [
            'correlationId' => $correlationId,
            'operation' => 'sync_access_data',
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($records),
        ]);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Upsert satu record ke tabel production_records.
     * Mapping nama kolom Access → MySQL.
     *
     * @param  array<string, mixed>  $record
     * @return int|null  Kembalikan ID jika berhasil insert/update
     */
    private function upsertRecord(array $record): ?int
    {
        $normalizedRecord = [];
        foreach ($record as $key => $value) {
            $normalizedRecord[strtoupper(trim((string) $key))] = $value;
        }

        $pick = static function (array $source, array $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = strtoupper(trim($alias));
                if (array_key_exists($normalizedAlias, $source)) {
                    return $source[$normalizedAlias];
                }
            }

            return null;
        };

        // Mapping kolom Access → MySQL (sesuaikan key dengan nama kolom di Access)
        $data = [
            'machine_no' => $pick($normalizedRecord, ['Machine No', 'machine_no']),
            'date' => $pick($normalizedRecord, ['Date', 'date']),
            'time_start' => $pick($normalizedRecord, ['Time Start', 'time_start']),
            'time_finish' => $pick($normalizedRecord, ['Time Finish', 'time_finish']),
            'operator' => $pick($normalizedRecord, ['Operator', 'operator']),
            'customer' => $pick($normalizedRecord, ['Customer', 'customer']),
            'part_no' => $pick($normalizedRecord, ['Part No', 'part_no']),
            'part_name' => $pick($normalizedRecord, ['Part Name', 'part_name']),
            'model' => $pick($normalizedRecord, ['Model', 'model']),
            'process' => $pick($normalizedRecord, ['Process', 'process']),
            'process_name' => $pick($normalizedRecord, ['Process Name', 'process_name']),
            'ppm' => $pick($normalizedRecord, ['PPM', 'ppm']),
            'time_input_qty_produksi' => $pick($normalizedRecord, ['Time Input Qty Produksi', 'time_input_qty_produksi']),
            'work_time_eff_h' => $pick($normalizedRecord, ['Work Time Eff H', 'work_time_eff_h']),
            'work_time_eff_m' => $pick($normalizedRecord, ['Work Time Eff M', 'work_time_eff_m']),
            'qty_target' => $pick($normalizedRecord, ['Qty Target', 'qty_target']),
            'qty_proses' => $pick($normalizedRecord, ['Qty Proses', 'qty_proses']),
            'productivity' => $pick($normalizedRecord, ['Productivity', 'productivity']),
            'target_productivity' => $pick($normalizedRecord, ['Target Productivity', 'target_productivity']),
            'durasi_m_total' => $pick($normalizedRecord, ['Durasi M Total', 'durasi_m_total']),
            'finish' => $pick($normalizedRecord, ['Finish', 'finish']),
            'id_pdr' => $pick($normalizedRecord, ['ID PDR', 'Id PDR', 'id_pdr', 'ID_PDR']),
            'dies_problem' => $pick($normalizedRecord, ['Dies Problem', 'dies_problem']),
            'preventive_mtn' => $pick($normalizedRecord, ['Preventive Mtn', 'preventive_mtn']),
            'remark' => $pick($normalizedRecord, ['Remark', 'remark', 'REMARK', 'Catatan', 'catatan']),
        ];

        // DEBUG P10
        if ($data['machine_no'] === 'P10' && $data['date'] === '2026-04-23') {
            file_put_contents('_debug_p10_raw_odcb.json', json_encode($record, JSON_PRETTY_PRINT));
        }

        // Cleaning semua data
        foreach ($data as $key => $val) {
            if ($val === null) {
                continue;
            }

            if (is_string($val)) {
                $val = trim($val);
            }

            // Jika kosong setelah di trim
            if ($val === '' || $val === ':' || $val === '-') {
                $data[$key] = null;
                continue;
            }

            $numericFields = ['ppm', 'qty_target', 'qty_proses', 'productivity', 'target_productivity', 'work_time_eff_h', 'work_time_eff_m', 'durasi_m_total'];
            
            if (in_array($key, $numericFields)) {
                // Konversi format HH:MM ke desimal jam (khusus durasi/waktu)
                if (is_string($val) && strpos($val, ':') !== false) {
                    $parts = explode(':', $val);
                    if (count($parts) >= 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                        $data[$key] = (float)$parts[0] + ((float)$parts[1] / 60);
                    } else {
                        $data[$key] = null;
                    }
                } else {
                    // Hilangkan % jika ada
                    $cleanStr = str_replace('%', '', trim((string) $val));

                    // Menangani koma (,) dan titik (.) dari berbagai regional settings
                    // Contoh MS Access format: "4,460.00" (US) atau "4.460,00" (ID)
                    $lastComma = strrpos($cleanStr, ',');
                    $lastDot = strrpos($cleanStr, '.');

                    if ($lastComma !== false && $lastDot !== false) {
                        if ($lastComma > $lastDot) {
                            // Format ID: 4.460,00 -> buang titik, lalu koma jadi titik
                            $cleanStr = str_replace('.', '', $cleanStr);
                            $cleanStr = str_replace(',', '.', $cleanStr);
                        } else {
                            // Format US: 4,460.00 -> buang koma
                            $cleanStr = str_replace(',', '', $cleanStr);
                        }
                    } elseif ($lastComma !== false) {
                        // Jika tidak ada titik, hanya koma
                        // Asumsi 1: Koma adalah desimal Indo (ex: "80,5")
                        $cleanStr = str_replace(',', '.', $cleanStr);
                    }

                    // Menghilangkan alfabet ganjil tapi menjaga format Scientific E, +, - dan titik.
                    $cleanVal = preg_replace('/[^0-9\.\-Ee\+]/', '', $cleanStr);

                    // Pencegahan Multi-dots (misal 4.460.000 menjadi 4.460000)
                    $parts = explode('.', $cleanVal);
                    if (count($parts) > 2) {
                        $cleanVal = str_replace('.', '', $cleanStr);
                    }

                    $data[$key] = is_numeric($cleanVal) ? (float) $cleanVal : null;
                }
            } else {
                $data[$key] = $val;
            }
        }

        // Format tanggal dan waktu untuk query MySQL (karena ODBC menghasilkan format datetime aneh seperti 1899-12-30)
        if (!empty($data['date'])) {
            $data['date'] = date('Y-m-d', strtotime($data['date']));
        }
        if (!empty($data['time_start'])) {
            // Hilangkan bagian tanggal jika ada (e.g. 1899-12-30 08:00:00 -> 08:00:00)
            $data['time_start'] = date('H:i:s', strtotime($data['time_start']));
        }
        if (!empty($data['time_finish'])) {
            $data['time_finish'] = date('H:i:s', strtotime($data['time_finish']));
        }

        // Filter null pada unique keys — semua harus ada
        $uniqueKeys = ['machine_no', 'date', 'time_start', 'part_no'];
        $searchKeys = array_intersect_key($data, array_flip($uniqueKeys));

        // Hanya upsert jika machine_no dan date tersedia (+ kita prioritaskan upsert ke valid record)
        if (empty($searchKeys['machine_no']) || empty($searchKeys['date'])) {
            return null;
        }

        try {
            $row = ProductionRecord::updateOrCreate($searchKeys, $data);
            return $row->id;
        } catch (\Illuminate\Database\QueryException $e) {
            // Jika terjadi error Duplicate Entry (1062) akibat race condition antar script sync
            if ($e->getCode() === '23000' && strpos($e->getMessage(), '1062') !== false) {
                // Fallback: lakukan update langsung menggunakan kondisional
                $query = ProductionRecord::query();
                foreach ($searchKeys as $k => $v) {
                    if ($v === null) {
                        $query->whereNull($k);
                    } else {
                        $query->where($k, $v);
                    }
                }
                $query->update($data);
                
                $row = ProductionRecord::where($searchKeys)->first();
                return $row ? $row->id : null;
            } else {
                // Lempar kembali jika itu bukan error duplicate
                throw $e;
            }
        }
    }
}
