<?php

namespace App\Http\Controllers;

use App\Models\LoadingMachineRecord;
use App\Services\AccessDatabaseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoadingMachineController extends Controller
{
    /**
     * Daftar mesin dari machine list.csv — Machine No => Machine Type Process
     */
    private const MACHINE_LIST = [
        'F01'   => 'Forming',    'F02'  => 'Forming',    'F03'  => 'Forming',
        'F04'   => 'Forming',    'F05'  => 'Forming',    'F06'  => 'Forming',
        'F07'   => 'Forming',    'F08'  => 'Forming',    'F09'  => 'Forming',
        'F10'   => 'Forming',    'FS01' => 'CNC',        'FS02' => 'CNC',
        'LT1'   => 'Uji Leak Test',
        'MSR'   => 'Mesin Sortir',
        'MW01'  => 'Welding',    'MWR01' => 'Welding',
        'P01'   => 'Stamping',   'P02'  => 'Stamping',   'P03'  => 'Stamping',
        'P04'   => 'Stamping',   'P05'  => 'Stamping',   'P06'  => 'Stamping',
        'P07'   => 'Stamping',   'P08'  => 'Stamping',   'P09'  => 'Stamping',
        'P10'   => 'Stamping',   'P11'  => 'Stamping',   'P12'  => 'Stamping',
        'P13'   => 'Stamping',   'P14'  => 'Stamping',   'P15'  => 'Stamping',
        'P16'   => 'Stamping',   'P17'  => 'Stamping',   'P18'  => 'Stamping',
        'P19'   => 'Stamping',   'P20'  => 'Stamping',   'P21'  => 'Stamping',
        'P22'   => 'Stamping',   'P23'  => 'Stamping',   'P24'  => 'Stamping',
        'P25'   => 'Stamping',   'P26'  => 'Stamping',   'P27'  => 'Stamping',
        'P28'   => 'Stamping',   'P29'  => 'Stamping',   'P30'  => 'Stamping',
        'P31'   => 'Stamping',   'P32'  => 'Stamping',
        'STNG'  => 'Stang',
        'SW01'  => 'Welding',    'SW02' => 'Welding',    'SW03' => 'Welding',
        'SW04'  => 'Welding',    'SW05' => 'Welding',    'SW06' => 'Welding',
        'T01'   => 'Tapping',    'T02'  => 'Tapping',    'T03'  => 'Tapping',
        'T04'   => 'Tapping',    'T05'  => 'Tapping',    'T06'  => 'Tapping',
        'T07'   => 'Tapping',    'T08'  => 'Tapping',    'T09'  => 'Tapping',
        'TW01'  => 'Welding',    'TW02' => 'Welding',
        'TWR01' => 'Welding',    'TWR02' => 'Welding',
    ];

    public function __construct(
        private readonly AccessDatabaseServiceInterface $accessDb
    ) {}

    /**
     * Tampilkan halaman dashboard Daily Loading Machine.
     * Data diambil dari MySQL (sudah di-sync dari Access).
     */
    public function index(Request $request): View
    {
        $dateMode    = $request->input('mode', 'daily');
        $date        = $request->input('date') ?? Carbon::today()->format('Y-m-d');
        $month       = $request->input('month') ?? Carbon::today()->format('Y-m');
        $machineType = $request->input('machine_type') ?? '';
        $machineNo   = $request->input('machine_no') ?? '';
        [$machineType, $machineNo] = $this->normalizeLoadingFilters($machineType, $machineNo);

        $records      = $this->queryRecords($dateMode, $date, $month, $machineType, $machineNo);
        $machineTypes = $this->getMachineTypes();
        $chartData    = $this->buildChartData($records, $dateMode);

        return view('dashboard.loading', [
            'records'              => $records,
            'machineTypes'         => $machineTypes,
            'machineList'          => self::MACHINE_LIST,
            'chartData'            => $chartData,
            'selectedDate'         => $date,
            'selectedMonth'        => $month,
            'selectedMachineType'  => $machineType,
            'selectedMachineNo'    => $machineNo,
            'dateMode'             => $dateMode,
        ]);
    }

    /**
     * Endpoint AJAX untuk filter data loading machine.
     */
    public function getData(Request $request): JsonResponse
    {
        $correlationId = uniqid('loading_dash_', true);

        $request->validate([
            'mode'         => ['required', 'in:daily,monthly'],
            'date'         => ['nullable', 'date_format:Y-m-d'],
            'month'        => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'machine_type' => ['nullable', 'string', 'max:100'],
        ]);

        $dateMode    = $request->input('mode', 'daily');
        $date        = $request->input('date') ?? Carbon::today()->format('Y-m-d');
        $month       = $request->input('month') ?? Carbon::today()->format('Y-m');
        $machineType = $request->input('machine_type') ?? '';
        $machineNo   = $request->input('machine_no') ?? '';
        [$machineType, $machineNo] = $this->normalizeLoadingFilters($machineType, $machineNo);

        Log::info('LoadingMachineController: getData', [
            'correlationId' => $correlationId,
            'operation'     => 'get_loading_data',
            'dateMode'      => $dateMode,
            'date'          => $date,
            'month'         => $month,
            'machineType'   => $machineType ?: 'all',
            'machineNo'     => $machineNo ?: 'all',
            'userId'        => $request->user()?->id,
        ]);

        $records   = $this->queryRecords($dateMode, $date, $month, $machineType, $machineNo);
        $chartData = $this->buildChartData($records, $dateMode);
        $summary   = $this->buildSummary($records);

        Log::info('LoadingMachineController: getData selesai', [
            'correlationId' => $correlationId,
            'operation'     => 'get_loading_data',
            'recordCount'   => count($records),
        ]);

        return response()->json([
            'chart'   => $chartData,
            'table'   => $records,
            'summary' => $summary,
        ]);
    }

    // =========================================================
    // Private helpers
    // =========================================================

    /**
     * Jika mesin spesifik dipilih, abaikan filter tipe (hindari kombinasi saling meniadakan).
     *
     * @return array{0: string, 1: string}
     */
    private function normalizeLoadingFilters(string $machineType, string $machineNo): array
    {
        if ($machineNo !== '') {
            return ['', $machineNo];
        }

        return [$machineType, $machineNo];
    }

    /**
     * Query data dari MySQL berdasarkan mode dan filter.
     *
     * @return array<int, array<string, mixed>>
     */
    private function queryRecords(
        string $dateMode,
        string $date,
        string $month,
        ?string $machineType,
        ?string $machineNo = null
    ): array {
        $query = LoadingMachineRecord::query()->orderBy('machine_no');

        // Mesin spesifik sudah unik: jangan AND-kan dengan tipe (bisa membuat hasil kosong bila tipe lama masih terpilih).
        if (($machineNo === null || $machineNo === '') && $machineType !== null && $machineType !== '') {
            $query->ofMesinType($machineType);
        }
        if ($machineNo !== null && $machineNo !== '') {
            $query->ofMachineNo($machineNo);
        }

        if ($dateMode === 'monthly') {
            $query->forMonth($month);
            $dbRows = $query->get();

            // Group by Date string
            $groupedByDate = [];
            foreach ($dbRows as $r) {
                $d = $r->date?->format('Y-m-d');
                if (!$d) continue;
                $groupedByDate[$d][] = $r;
            }

            // Generate all dates in the month
            [$year, $mon] = explode('-', $month);
            $daysInMonth = \Carbon\Carbon::create($year, $mon)->daysInMonth;

            $result = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dstr = sprintf('%04d-%02d-%02d', $year, $mon, $i);
                
                if (isset($groupedByDate[$dstr])) {
                    $dayRows = $groupedByDate[$dstr];
                    $wt = 0; $dt = 0; $we = 0; $loadSum = 0;
                    foreach ($dayRows as $r) {
                        $wt += (float)$r->work_time_mc;
                        $dt += (float)$r->durasi_m_total;
                        $we += (float)$r->work_time_eff_m;
                    }
                    $avgLoading = count($dayRows) > 0 ? ($we / (count($dayRows) * 840)) : 0;
                    
                    $result[] = [
                        'Machine No'           => $machineNo ?: 'Semua',
                        'Date'                 => $dstr,
                        'Work Time Mc'         => 840,
                        'SumOfDurasi M Total'  => $dt,
                        'SumOfWork Time Eff M' => $we,
                        '% Loading'            => $avgLoading,
                        'Machine Type Process' => null,
                        'Mesin Type'           => null,
                    ];
                } else {
                    $result[] = [
                        'Machine No'           => $machineNo ?: 'Semua',
                        'Date'                 => $dstr,
                        'Work Time Mc'         => 840,
                        'SumOfDurasi M Total'  => null,
                        'SumOfWork Time Eff M' => null,
                        '% Loading'            => null, // null akan jadi '-' di tabel, 0 di chart
                        'Machine Type Process' => null,
                        'Mesin Type'           => null,
                    ];
                }
            }
            return $result;

        } else {
            // == DAILY MODE ==
            $query->forDate($date);
            $dbRows = $query->get()->keyBy('machine_no');

            $masterList = self::MACHINE_LIST;
            if ($machineNo !== null && $machineNo !== '') {
                $masterList = array_intersect_key($masterList, [$machineNo => true]);
            } elseif ($machineType !== null && $machineType !== '') {
                if ($machineType === 'Stamping') {
                    $masterList = array_filter($masterList, fn ($t) => $t === 'Stamping');
                } elseif ($machineType === 'Non Stamping') {
                    $masterList = array_filter($masterList, fn ($t) => $t !== 'Stamping');
                }
            }

            $result = [];
            foreach ($masterList as $no => $typeProcess) {
                $r = $dbRows->get($no);
                $result[] = [
                    'Machine No'           => $no,
                    'Date'                 => $r?->date?->format('Y-m-d') ?? $date,
                    'Work Time Mc'         => 840,
                    'SumOfDurasi M Total'  => $r?->durasi_m_total,
                    'SumOfWork Time Eff M' => $r?->work_time_eff_m,
                    '% Loading'            => $r && $r->work_time_eff_m !== null ? ((float)$r->work_time_eff_m / 840) : null,
                    'Machine Type Process' => $r?->machine_type_process ?? $typeProcess,
                    'Mesin Type'           => $r?->mesin_type,
                ];
            }
            return $result;
        }
    }

    /**
     * Ambil daftar Mesin Type yang unik dari MySQL.
     *
     * @return list<string>
     */
    private function getMachineTypes(): array
    {
        return LoadingMachineRecord::query()
            ->whereNotNull('mesin_type')
            ->distinct()
            ->orderBy('mesin_type')
            ->pluck('mesin_type')
            ->all();
    }

    /**
     * Bangun data untuk bar chart % Loading per Machine No.
     * Nilai yang dikembalikan sudah dalam 0–100 (konversi dari desimal Access).
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{labels: list<string>, values: list<float>}
     */
    private function buildChartData(array $records, string $dateMode): array
    {
        $labels = [];
        $values = [];

        if ($dateMode === 'monthly') {
            // Mode Bulanan: X-axis adalah Tanggal
            foreach ($records as $row) {
                $labels[] = \Carbon\Carbon::parse($row['Date'])->format('d-M-Y');
                $values[] = round((float) ($row['% Loading'] ?? 0) * 100, 1);
            }
            return ['labels' => $labels, 'values' => $values];
        }

        // Mode Harian: X-axis adalah Mesin (sudah di-pad via queryRecords)
        foreach ($records as $row) {
            $labels[] = (string) ($row['Machine No'] ?? 'Unknown');
            $values[] = round((float) ($row['% Loading'] ?? 0) * 100, 1);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Ringkasan statistik. avg_loading sudah dalam 0–100.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{total_machines: int, avg_loading: float}
     */
    private function buildSummary(array $records): array
    {
        if (empty($records)) {
            return ['total_machines' => 0, 'avg_loading' => 0.0];
        }

        $machines    = array_unique(array_column($records, 'Machine No'));
        $loadings    = array_column($records, '% Loading');
        $loadingNums = array_map('floatval', array_filter($loadings, fn ($v) => $v !== null));

        $avgPercent = count($loadingNums)
            ? round((array_sum($loadingNums) / count($loadingNums)) * 100, 1)
            : 0.0;

        return [
            'total_machines' => count($machines),
            'avg_loading'    => $avgPercent,
        ];
    }
}
