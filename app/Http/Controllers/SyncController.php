<?php

namespace App\Http\Controllers;

use App\Services\AccessDatabaseServiceInterface;
use App\Services\AccessSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function __construct(
        private readonly AccessSyncService $syncService,
        private readonly AccessDatabaseServiceInterface $accessDb,
    ) {}

    public function sync(Request $request): JsonResponse
    {
        $correlationId = uniqid('sync_api_', true);

        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $date = $request->input('date');
        $days = $request->has('days') ? (int) $request->input('days') : ($date ? null : 7);

        Log::info('SyncController: Manual sync requested', [
            'correlationId' => $correlationId,
            'date' => $date,
            'days' => $days,
            'userId' => $request->user()?->id,
        ]);

        if (!$this->accessDb->testConnection()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke database Access: ' . ($this->accessDb->getLastError() ?? 'Unknown error'),
            ], 500);
        }

        $prodResult = $this->syncService->sync($date, $days);
        $loadResult = $this->syncService->syncLoading($date, null, $days);

        $scopeMsg = $date ? "tanggal $date" : "$days hari terakhir";

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi $scopeMsg selesai ({$prodResult['synced']} record produksi, {$loadResult['synced']} record loading).",
            'production' => $prodResult,
            'loading' => $loadResult,
        ]);
    }
}
