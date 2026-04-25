<?php

namespace App\Services;

/**
 * Contract untuk service koneksi ke Microsoft Access.
 * Memungkinkan mock saat testing tanpa file Access nyata.
 */
interface AccessDatabaseServiceInterface
{
    /**
     * Ambil data produksi dari database Access.
     *
     * @param  string|null  $date  Format Y-m-d, null = semua data
     * @return array<int, array<string, mixed>>
     */
    public function fetchRecords(?string $date = null): array;

    /**
     * Ambil data loading machine dari database Access.
     *
     * @param  string|null  $date       Format Y-m-d untuk filter per hari
     * @param  string|null  $month      Format Y-m untuk filter per bulan
     * @param  string|null  $machineType  Nilai Mesin Type untuk filter, null = semua
     * @return array<int, array<string, mixed>>
     */
    public function fetchLoadingRecords(
        ?string $date = null,
        ?string $month = null,
        ?string $machineType = null
    ): array;

    /**
     * Ambil daftar unik nilai Mesin Type dari query Loading Mc.
     *
     * @return array<int, string>
     */
    public function fetchLoadingMachineTypes(): array;

    /**
     * Test apakah koneksi ke database Access berhasil.
     */
    public function testConnection(): bool;
}
