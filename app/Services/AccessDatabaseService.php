<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * Service koneksi ke Microsoft Access via PDO ODBC.
 * Mengambil data produksi dari file .accdb/.mdb untuk disinkronkan ke MySQL.
 */
class AccessDatabaseService implements AccessDatabaseServiceInterface
{
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = config('services.access_db.path', env('ACCESS_DB_PATH', ''));
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRecords(?string $date = null): array
    {
        $correlationId = uniqid('sync_', true);

        Log::info('AccessDatabaseService: Memulai fetch records', [
            'correlationId' => $correlationId,
            'operation' => 'fetch_records',
            'date' => $date ?? 'all',
            'dbPath' => $this->dbPath,
        ]);

        $pdo = $this->createConnection($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            // Query ke tabel Access — sesuaikan nama tabel dengan database Access
            $sql = 'SELECT * FROM [Query - Daily PDR Live Excel]';
            $params = [];

            if ($date !== null) {
                $sql .= ' WHERE [Date] = ?';
                $params[] = $date;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Log::info('AccessDatabaseService: Fetch records berhasil', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_records',
                'recordCount' => count($records),
                'duration' => 'completed',
            ]);

            return $records;
        } catch (PDOException $e) {
            dump("Error Fetch Records PDOException:");
            dump($e->getMessage());

            Log::error('AccessDatabaseService: Gagal fetch records', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_records',
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function fetchLoadingRecords(
        ?string $date = null,
        ?string $month = null,
        ?string $machineType = null
    ): array {
        $correlationId = uniqid('loading_', true);

        Log::info('AccessDatabaseService: Memulai fetch loading records', [
            'correlationId' => $correlationId,
            'operation' => 'fetch_loading_records',
            'date' => $date ?? 'null',
            'month' => $month ?? 'null',
            'machineType' => $machineType ?? 'all',
        ]);

        $pdo = $this->createConnection($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            $sql = 'SELECT * FROM [Query - Daily Loading Mc]';
            $conditions = [];
            $params = [];

            // Filter per hari (prioritas di atas bulan)
            if ($date !== null) {
                $conditions[] = '[Date] = ?';
                $params[] = $date;
            } elseif ($month !== null) {
                // Format $month: 'Y-m', contoh '2026-04'
                [$year, $mon] = explode('-', $month);
                $conditions[] = 'Year([Date]) = ?';
                $conditions[] = 'Month([Date]) = ?';
                $params[] = (int) $year;
                $params[] = (int) $mon;
            }

            if ($machineType !== null && $machineType !== '') {
                $conditions[] = '[Mesin Type] = ?';
                $params[] = $machineType;
            }

            if (! empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $sql .= ' ORDER BY [Machine No]';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Log::info('AccessDatabaseService: Fetch loading records berhasil', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_loading_records',
                'recordCount' => count($records),
            ]);

            return $records;
        } catch (PDOException $e) {
            Log::error('AccessDatabaseService: Gagal fetch loading records', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_loading_records',
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function fetchLoadingMachineTypes(): array
    {
        $correlationId = uniqid('mctypes_', true);

        $pdo = $this->createConnection($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query(
                'SELECT DISTINCT [Mesin Type] FROM [Query - Daily Loading Mc] WHERE [Mesin Type] IS NOT NULL ORDER BY [Mesin Type]'
            );
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return array_filter($rows, fn ($v) => $v !== null && $v !== '');
        } catch (PDOException $e) {
            Log::error('AccessDatabaseService: Gagal fetch machine types', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_loading_machine_types',
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }



    /**
     * {@inheritDoc}
     */
    public function testConnection(): bool
    {
        $correlationId = uniqid('conntest_', true);

        $pdo = $this->createConnection($correlationId);

        return $pdo !== null;
    }

    /**
     * Buat koneksi PDO ke file Microsoft Access.
     * Mengembalikan null jika gagal (file tidak ada, driver tidak terinstall, dsb).
     */
    private function createConnection(string $correlationId): ?PDO
    {
        if (empty($this->dbPath)) {
            Log::warning('AccessDatabaseService: ACCESS_DB_PATH belum dikonfigurasi', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
            ]);

            return null;
        }

        if (! file_exists($this->dbPath)) {
            Log::warning('AccessDatabaseService: File database Access tidak ditemukan', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
                'path' => $this->dbPath,
            ]);

            return null;
        }

        try {
            $dsn = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$this->dbPath}";

            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            Log::info('AccessDatabaseService: Koneksi berhasil', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
            ]);

            return $pdo;
        } catch (PDOException $e) {
            dump("Error PDO Connection:");
            dump($e->getMessage());
            
            Log::error('AccessDatabaseService: Gagal membuat koneksi', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
