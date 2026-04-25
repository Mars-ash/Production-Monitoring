<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionRecord extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'machine_no',
        'date',
        'time_start',
        'time_finish',
        'operator',
        'customer',
        'part_no',
        'part_name',
        'model',
        'process',
        'process_name',
        'ppm',
        'time_input_qty_produksi',
        'work_time_eff_h',
        'work_time_eff_m',
        'qty_target',
        'qty_proses',
        'productivity',
        'target_productivity',
        'durasi_m_total',
        'finish',
        'id_pdr',
        'dies_problem',
        'preventive_mtn',
        'remark',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'ppm' => 'decimal:2',
            'work_time_eff_h' => 'decimal:2',
            'work_time_eff_m' => 'decimal:2',
            'qty_target' => 'integer',
            'qty_proses' => 'integer',
            'productivity' => 'decimal:2',
            'target_productivity' => 'decimal:2',
            'durasi_m_total' => 'decimal:2',
        ];
    }

    /**
     * Scope: filter berdasarkan tanggal.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Filter status produksi dari kolom finish (mis. RUN / FINISH).
     */
    public function scopeWithProductionStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        $normalized = strtoupper(trim($status));

        return $query->whereRaw('UPPER(TRIM(COALESCE(finish, \'\'))) = ?', [$normalized]);
    }

    /**
     * Override parameter target dari DB dan memaksanya selalu di angka 85%
     */
    public function getTargetProductivityAttribute($value)
    {
        return 0.85;
    }

    /**
     * Accessor: apakah produktivitas mencapai target.
     */
    public function getIsOnTargetAttribute(): bool
    {
        if (is_null($this->productivity)) {
            return false;
        }

        return (float) $this->productivity >= 0.85;
    }
}
