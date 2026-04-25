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
     * Tampilkan halaman dashboard.
     */
    public function index(Request $request): View
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $status = $this->normalizeProductionStatus($request->input('status'));

        $records = ProductionRecord::forDate($date)
            ->withProductionStatus($status)
            ->orderBy('machine_no')
            ->orderBy('time_start')
            ->get();

        $stats = $this->calculateStats($records);

        return view('dashboard.index', [
            'records' => $records,
            'stats' => $stats,
            'selectedDate' => $date,
            'selectedStatus' => $status,
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
        ]);

        $date = $request->input('date');
        $status = $this->normalizeProductionStatus($request->input('status'));

        Log::info('DashboardController: getData', [
            'correlationId' => $correlationId,
            'operation' => 'get_dashboard_data',
            'date' => $date,
            'status' => $status ?: null,
            'userId' => $request->user()?->id,
        ]);

        $records = ProductionRecord::forDate($date)
            ->withProductionStatus($status)
            ->orderBy('machine_no')
            ->orderBy('time_start')
            ->get();

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
            'time_finish' => $record->time_finish,
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
}
