<?php

namespace App\Services;

use App\Models\ProductionRecord;
use Illuminate\Support\Facades\Log;

class AccessSyncService
{
    public function __construct(
        private readonly AccessDatabaseServiceInterface $accessService,
        private readonly AccessDataTransformer $transformer,
    ) {}

    /**
     * Perform full or daily synchronization.
     */
    public function sync(?string $date = null): array
    {
        $correlationId = uniqid('sync_svc_', true);
        
        Log::info('AccessSyncService: Sinkronisasi dimulai', [
            'correlationId' => $correlationId,
            'date' => $date ?? 'today',
        ]);

        $rawRecords = $this->accessService->fetchRecords($date);

        if (empty($rawRecords)) {
            Log::warning('AccessSyncService: Tidak ada data ditemukan', ['correlationId' => $correlationId]);
            return ['synced' => 0, 'deleted' => 0, 'status' => 'no_data'];
        }

        $toUpsert = [];
        foreach ($rawRecords as $raw) {
            $transformed = $this->transformer->transform($raw);
            if ($transformed) {
                $toUpsert[] = $transformed;
            }
        }

        if (empty($toUpsert)) {
            return ['synced' => 0, 'deleted' => 0, 'status' => 'no_valid_data'];
        }

        $chunks = array_chunk($toUpsert, 500);
        $syncedCount = 0;

        foreach ($chunks as $chunk) {
            ProductionRecord::upsert(
                $chunk,
                ['machine_no', 'date', 'time_start', 'part_no'],
                array_keys($chunk[0])
            );
            $syncedCount += count($chunk);
        }

        $deletedCount = 0;
        if ($date) {
            $accessIds = array_filter(array_column($toUpsert, 'id_pdr'));
            if (!empty($accessIds)) {
                $deletedCount = ProductionRecord::where('date', $date)
                    ->whereNotIn('id_pdr', $accessIds)
                    ->delete();
            }
        }

        Log::info('AccessSyncService: Sinkronisasi selesai', [
            'correlationId' => $correlationId,
            'synced' => $syncedCount,
            'deleted' => $deletedCount,
        ]);

        return [
            'synced' => $syncedCount,
            'deleted' => $deletedCount,
            'status' => 'success'
        ];
    }

    /**
     * Perform synchronization for loading machine records.
     */
    public function syncLoading(?string $date = null, ?string $month = null): array
    {
        $correlationId = uniqid('sync_load_svc_', true);

        Log::info('AccessSyncService: Sinkronisasi Loading dimulai', [
            'correlationId' => $correlationId,
            'date' => $date ?? 'all',
            'month' => $month ?? '-',
        ]);

        $rawRecords = $this->accessService->fetchLoadingRecords($date, $month);

        if (empty($rawRecords)) {
            Log::warning('AccessSyncService: Tidak ada data loading ditemukan', ['correlationId' => $correlationId]);
            return ['synced' => 0, 'status' => 'no_data'];
        }

        $toUpsert = [];
        foreach ($rawRecords as $raw) {
            $machineNo = trim((string) ($raw['Machine No'] ?? ''));
            $rawDate   = $raw['Date'] ?? null;

            if ($machineNo === '' || $rawDate === null) continue;

            $dateStr = date('Y-m-d', strtotime((string) $rawDate));

            $toUpsert[] = [
                'machine_no'           => $machineNo,
                'date'                 => $dateStr,
                'work_time_mc'         => $this->parseNumeric($raw['Work Time Mc'] ?? null),
                'durasi_m_total'       => $this->parseNumeric($raw['SumOfDurasi M Total'] ?? null),
                'work_time_eff_m'      => $this->parseNumeric($raw['SumOfWork Time Eff M'] ?? null),
                'loading_pct'          => $this->parseNumeric($raw['% Loading'] ?? null),
                'machine_type_process' => $this->parseString($raw['Machine Type Process'] ?? null),
                'mesin_type'           => $this->parseString($raw['Mesin Type'] ?? null),
            ];
        }

        if (empty($toUpsert)) {
            return ['synced' => 0, 'status' => 'no_valid_data'];
        }

        $chunks = array_chunk($toUpsert, 500);
        $syncedCount = 0;

        foreach ($chunks as $chunk) {
            \App\Models\LoadingMachineRecord::upsert(
                $chunk,
                ['machine_no', 'date'],
                array_keys($chunk[0])
            );
            $syncedCount += count($chunk);
        }

        Log::info('AccessSyncService: Sinkronisasi Loading selesai', [
            'correlationId' => $correlationId,
            'synced' => $syncedCount,
        ]);

        return [
            'synced' => $syncedCount,
            'status' => 'success'
        ];
    }

    private function parseNumeric($val): ?float
    {
        if ($val === null || $val === '' || $val === '-' || $val === ':') {
            return null;
        }

        $str = trim((string) $val);

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

    private function parseString(mixed $val): ?string
    {
        if ($val === null) return null;
        $str = trim((string) $val);
        return $str === '' || $str === '-' ? null : $str;
    }
}
