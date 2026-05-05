<?php

namespace App\Http\Controllers;

use App\Models\ProductionRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Mapping mesin ke kategori (Stamping / Non Stamping).
     * Digunakan untuk filter Machine Type di Daily Production.
     */
    private const STAMPING_MACHINES = [
        'P01', 'P02', 'P03', 'P04', 'P05', 'P06', 'P07', 'P08', 'P09', 'P10',
        'P11', 'P12', 'P13', 'P14', 'P15', 'P16', 'P17', 'P18', 'P19', 'P20',
        'P21', 'P22', 'P23', 'P24', 'P25', 'P26', 'P27', 'P28', 'P29', 'P30',
        'P31', 'P32',
    ];

    /**
     * Tampilkan halaman dashboard.
     */
    public function index(Request $request): View
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        $rawStatus = $request->has('status') ? $request->input('status') : 'RUN';
        $status = $this->normalizeProductionStatus($rawStatus);
        $machineType = $request->input('machine_type', '');

        $records = ProductionRecord::forDate($date)
            ->withProductionStatus($status)
            ->orderBy('machine_no')
            ->orderBy('time_start')
            ->get();

        // Filter by machine type
        if ($machineType !== null && $machineType !== '') {
            $records = $this->filterByMachineType($records, $machineType);
        }

        $stats = $this->calculateStats($records);

        return view('dashboard.index', [
            'records' => $records,
            'stats' => $stats,
            'selectedDate' => $date,
            'selectedStatus' => $status,
            'selectedMachineType' => $machineType,
        ]);
    }

    /**
     * Endpoint AJAX untuk fetch data dashboard berdasarkan tanggal.
     */
    public function getData(Request $request): JsonResponse
    {
        $correlationId = uniqid('dash_', true);

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['nullable', 'string', 'max:20'],
            'machine_type' => ['nullable', 'string', 'max:20'],
        ]);

        $date = $request->input('date');
        $rawStatus = $request->has('status') ? $request->input('status') : 'RUN';
        $status = $this->normalizeProductionStatus($rawStatus);
        $machineType = $request->input('machine_type', '');

        Log::info('DashboardController: getData', [
            'correlationId' => $correlationId,
            'operation' => 'get_dashboard_data',
            'date' => $date,
            'status' => $status ?: null,
            'machineType' => $machineType ?: 'all',
            'userId' => $request->user()?->id,
        ]);

        $records = ProductionRecord::forDate($date)
            ->withProductionStatus($status)
            ->orderBy('machine_no')
            ->orderBy('time_start')
            ->get();

        // Filter by machine type
        if ($machineType !== null && $machineType !== '') {
            $records = $this->filterByMachineType($records, $machineType);
        }

        $stats = $this->calculateStats($records);

        $productivityByMachine = $records->groupBy('machine_no')->map(function ($group) {
            return round((float) $group->avg('productivity') * 100, 2);
        });

        // Data chart: qty target vs actual
        $qtyData = [
            'target' => (int) $records->sum('qty_target'),
            'actual' => (int) $records->sum('qty_proses'),
        ];

        // Data chart: distribusi per customer
        $customerDistribution = $records->groupBy('customer')->map(function ($group) {
            return $group->count();
        })->filter(fn ($count, $customer) => ! empty($customer));

        // Data tabel
        $tableData = $records->map(fn (ProductionRecord $record) => [
            'id' => $record->id,
            'machine_no' => $record->machine_no,
            'part_name' => $record->part_name,
            'time_start' => $record->time_start,
            'time_input_qty_produksi' => $record->time_input_qty_produksi,
            'time_finish' => $record->time_finish,
            'finish' => $record->finish,
            'qty_proses' => $record->qty_proses,
            'productivity' => $record->productivity,
            'target_productivity' => $record->target_productivity,
            'is_on_target' => $record->is_on_target,
        ]);

        return response()->json([
            'stats' => $stats,
            'charts' => [
                'productivityByMachine' => $productivityByMachine,
                'qtyData' => $qtyData,
                'customerDistribution' => $customerDistribution,
            ],
            'table' => $tableData,
        ]);
    }

    /**
     * Tampilkan detail satu record produksi.
     */
    public function show(int $id): View
    {
        $record = ProductionRecord::findOrFail($id);

        // Ambil semua record pada tanggal yang sama, urut sesuai dashboard
        $siblingIds = ProductionRecord::forDate($record->date->format('Y-m-d'))
            ->orderBy('machine_no')
            ->orderBy('time_start')
            ->pluck('id')
            ->values();

        $currentIndex = $siblingIds->search($record->id);
        $previousId = $currentIndex > 0 ? $siblingIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < $siblingIds->count() - 1 ? $siblingIds[$currentIndex + 1] : null;

        return view('dashboard.detail', [
            'record' => $record,
            'previousId' => $previousId,
            'nextId' => $nextId,
        ]);
    }

    /**
     * Hitung statistik ringkasan dari koleksi record.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, ProductionRecord>  $records
     * @return array<string, mixed>
     */
    private function calculateStats($records): array
    {
        $activeMachines = $records->pluck('machine_no')->unique()->count();
        $avgProductivity = $records->count() > 0
            ? round((float) $records->avg('productivity'), 2)
            : 0;
        $totalOutput = (int) $records->sum('qty_proses');
        $totalTarget = (int) $records->sum('qty_target');

        return [
            'active_machines' => $activeMachines,
            'avg_productivity' => $avgProductivity,
            'total_output' => $totalOutput,
            'total_target' => $totalTarget,
        ];
    }

    /**
     * Normalisasi query status RUN/FINISH (string kosong = semua).
     */
    private function normalizeProductionStatus(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $upper = strtoupper(trim($value));

        return in_array($upper, ['RUN', 'FINISH'], true) ? $upper : '';
    }

    /**
     * Filter koleksi record berdasarkan tipe mesin (Stamping / Non Stamping).
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $records
     */
    private function filterByMachineType($records, string $machineType)
    {
        if ($machineType === 'Stamping') {
            return $records->filter(
                fn ($r) => in_array($r->machine_no, self::STAMPING_MACHINES, true)
            )->values();
        }

        if ($machineType === 'Non Stamping') {
            return $records->filter(
                fn ($r) => ! in_array($r->machine_no, self::STAMPING_MACHINES, true)
            )->values();
        }

        return $records;
    }
}
