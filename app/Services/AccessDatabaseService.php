<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;


class AccessDatabaseService implements AccessDatabaseServiceInterface
{
    private string $dbPath;
    private ?string $lastError = null;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->dbPath = (string) (config('services.access_db.path') ?? '');
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

        $pdo = $this->getPdo($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            // Jika tanggal spesifik, fetch langsung
            if ($date !== null) {
                $stmt = $pdo->prepare('SELECT * FROM [Query - Daily PDR Live Web] WHERE [Date] = ?');
                $stmt->execute([$date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Fetch semua: ODBC driver sering memotong hasil jika terlalu banyak row (>2000).
                // Solusi: fetch daftar tanggal unik dulu, lalu fetch per tanggal.
                $dateStmt = $pdo->query('SELECT DISTINCT [Date] FROM [Query - Daily PDR Live Web] ORDER BY [Date]');
                $dates = $dateStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                $records = [];
                foreach ($dates as $d) {
                    try {
                        $stmt = $pdo->prepare('SELECT * FROM [Query - Daily PDR Live Web] WHERE [Date] = ?');
                        $stmt->execute([$d]);
                        $batch = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($batch as $row) {
                            $records[] = $row;
                        }
                    } catch (PDOException $pe) {
                        Log::warning('AccessDatabaseService: Gagal fetch records untuk tanggal ' . $d, [
                            'correlationId' => $correlationId,
                            'error' => $pe->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('AccessDatabaseService: Fetch records berhasil', [
                'correlationId' => $correlationId,
                'operation' => 'fetch_records',
                'recordCount' => count($records),
                'duration' => 'completed',
            ]);

            return $records;
        } catch (PDOException $e) {
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

        $pdo = $this->getPdo($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            // ODBC driver often fails or truncates results if there are many rows (>2000).
            // If no specific date/month filter is given, we fetch by unique dates to avoid this limit.
            if ($date === null && $month === null) {
                $dateStmt = $pdo->query('SELECT DISTINCT [Date] FROM [Query - Daily Loading Mc Web] WHERE [Date] IS NOT NULL ORDER BY [Date]');
                $dates = $dateStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                $records = [];
                foreach ($dates as $d) {
                    try {
                        $sql = 'SELECT * FROM [Query - Daily Loading Mc Web] WHERE [Date] = ?';
                        $params = [$d];

                        if ($machineType !== null && $machineType !== '') {
                            $sql .= ' AND [Mesin Type] = ?';
                            $params[] = $machineType;
                        }

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $batch = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($batch as $row) {
                            $records[] = $row;
                        }
                    } catch (PDOException $pe) {
                        Log::warning('AccessDatabaseService: Gagal fetch loading records untuk tanggal ' . $d, [
                            'correlationId' => $correlationId,
                            'error' => $pe->getMessage(),
                        ]);
                    }
                }
            } else {
                $sql = 'SELECT * FROM [Query - Daily Loading Mc Web]';
                $conditions = [];
                $params = [];

                if ($date !== null) {
                    $conditions[] = '[Date] = ?';
                    $params[] = $date;
                } elseif ($month !== null) {
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
            }

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

        $pdo = $this->getPdo($correlationId);
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query(
                'SELECT DISTINCT [Mesin Type] FROM [Query - Daily Loading Mc Web] WHERE [Mesin Type] IS NOT NULL ORDER BY [Mesin Type]'
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


        return $this->getPdo($correlationId) !== null;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function getPdo(string $correlationId): ?PDO
    {
        return $this->pdo ??= $this->createConnection($correlationId);
    }

    private function createConnection(string $correlationId): ?PDO
    {
        $this->lastError = null;

        if (empty($this->dbPath)) {
            $this->lastError = "ACCESS_DB_PATH belum dikonfigurasi di file .env";
            Log::warning('AccessDatabaseService: ACCESS_DB_PATH belum dikonfigurasi', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
            ]);

            return null;
        }

        if (! file_exists($this->dbPath)) {
            $this->lastError = "File database Access tidak ditemukan di path: {$this->dbPath}";
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
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'IM002') || str_contains($errorMsg, 'Data source name not found')) {
                $errorMsg .= " (Sebab: Driver ODBC 64-bit 'Microsoft Access Driver (*.mdb, *.accdb)' tidak ditemukan di Windows. Harap install Microsoft Access Database Engine 2016/2010 Redistributable 64-bit).";
            }

            $this->lastError = "PDO/ODBC Error: " . $errorMsg;
            Log::error('AccessDatabaseService: Gagal membuat koneksi', [
                'correlationId' => $correlationId,
                'operation' => 'create_connection',
                'error' => $errorMsg,
            ]);

            return null;
        }
    }
}
