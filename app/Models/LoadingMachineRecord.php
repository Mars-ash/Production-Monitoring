<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LoadingMachineRecord extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'machine_no',
        'date',
        'work_time_mc',
        'durasi_m_total',
        'work_time_eff_m',
        'loading_pct',
        'machine_type_process',
        'mesin_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'work_time_mc'   => 'decimal:4',
            'durasi_m_total' => 'decimal:4',
            'work_time_eff_m' => 'decimal:4',
            'loading_pct'    => 'decimal:6',
        ];
    }

    /**
     * Accessor: % Loading dalam persen (0–100), nilai Access desimal (0–1).
     */
    public function getLoadingPercentAttribute(): float
    {
        return round((float) $this->loading_pct * 100, 1);
    }

    /**
     * Scope: filter berdasarkan tanggal.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope: filter berdasarkan bulan (Y-m).
     */
    public function scopeForMonth(Builder $query, string $month): Builder
    {
        [$year, $mon] = explode('-', $month);

        return $query->whereYear('date', $year)->whereMonth('date', $mon);
    }

    /**
     * Scope: filter berdasarkan Mesin Type.
     */
    public function scopeOfMesinType(Builder $query, string $type): Builder
    {
        return $query->where('mesin_type', $type);
    }

    /**
     * Scope: filter berdasarkan Machine No spesifik.
     */
    public function scopeOfMachineNo(Builder $query, string $machineNo): Builder
    {
        return $query->where('machine_no', $machineNo);
    }
}
