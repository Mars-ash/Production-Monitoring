<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class AccessDataTransformer
{
    /**
     * Fields that should be treated as numeric.
     */
    private const NUMERIC_FIELDS = [
        'ppm', 'qty_target', 'qty_proses', 'productivity', 
        'target_productivity', 'work_time_eff_h', 
        'work_time_eff_m', 'durasi_m_total'
    ];

    /**
     * Transform raw Access record to normalized MySQL format.
     */
    public function transform(array $record): ?array
    {
        $normalizedRaw = [];
        foreach ($record as $key => $value) {
            $normalizedRaw[strtoupper(trim((string) $key))] = $value;
        }

        // Mapping Access -> MySQL
        $data = [
            'machine_no'              => $this->pick($normalizedRaw, ['Machine No', 'machine_no']),
            'date'                    => $this->pick($normalizedRaw, ['Date', 'date']),
            'time_start'              => $this->pick($normalizedRaw, ['Time Start', 'time_start']),
            'time_finish'             => $this->pick($normalizedRaw, ['Time Finish', 'time_finish']),
            'operator'                => $this->pick($normalizedRaw, ['Operator', 'operator']),
            'customer'                => $this->pick($normalizedRaw, ['Customer', 'customer']),
            'part_no'                 => $this->pick($normalizedRaw, ['Part No', 'part_no']),
            'part_name'               => $this->pick($normalizedRaw, ['Part Name', 'part_name']),
            'model'                   => $this->pick($normalizedRaw, ['Model', 'model']),
            'process'                 => $this->pick($normalizedRaw, ['Process', 'process']),
            'process_name'            => $this->pick($normalizedRaw, ['Process Name', 'process_name']),
            'ppm'                     => $this->pick($normalizedRaw, ['PPM', 'ppm']),
            'time_input_qty_produksi' => $this->pick($normalizedRaw, ['Time Input Qty Produksi', 'time_input_qty_produksi']),
            'work_time_eff_h'         => $this->pick($normalizedRaw, ['Work Time Eff H', 'work_time_eff_h']),
            'work_time_eff_m'         => $this->pick($normalizedRaw, ['Work Time Eff M', 'work_time_eff_m']),
            'qty_target'              => $this->pick($normalizedRaw, ['Qty Target', 'qty_target']),
            'qty_proses'              => $this->pick($normalizedRaw, ['Qty Proses', 'qty_proses']),
            'productivity'            => $this->pick($normalizedRaw, ['Productivity', 'productivity']),
            'target_productivity'     => $this->pick($normalizedRaw, ['Target Productivity', 'target_productivity']),
            'durasi_m_total'          => $this->pick($normalizedRaw, ['Durasi M Total', 'durasi_m_total']),
            'finish'                  => $this->pick($normalizedRaw, ['Finish', 'finish']),
            'id_pdr'                  => $this->pick($normalizedRaw, ['ID PDR', 'Id PDR', 'id_pdr', 'ID_PDR']),
            'dies_problem'            => $this->pick($normalizedRaw, ['Dies Problem', 'dies_problem']),
            'preventive_mtn'          => $this->pick($normalizedRaw, ['Preventive Mtn', 'preventive_mtn']),
            'remark'                  => $this->pick($normalizedRaw, ['Remark', 'remark', 'REMARK', 'Catatan', 'catatan']),
        ];

        // Validasi Mandatory Keys
        if (empty($data['machine_no']) || empty($data['date'])) {
            return null;
        }

        // Clean & Format
        foreach ($data as $key => $val) {
            if ($val === null) continue;

            if (is_string($val)) {
                $val = trim($val);
            }

            if ($val === '' || $val === ':' || $val === '-') {
                $data[$key] = null;
                continue;
            }

            if (in_array($key, self::NUMERIC_FIELDS)) {
                $data[$key] = $this->parseNumeric($val);
            } else {
                $data[$key] = $val;
            }
        }

        // Formatting dates/times for MySQL
        if (!empty($data['date'])) {
            $data['date'] = date('Y-m-d', strtotime((string)$data['date']));
        }
        if (!empty($data['time_start'])) {
            $data['time_start'] = date('H:i:s', strtotime((string)$data['time_start']));
        }
        if (!empty($data['time_finish'])) {
            $data['time_finish'] = date('H:i:s', strtotime((string)$data['time_finish']));
        }
        if (!empty($data['time_input_qty_produksi'])) {
            $data['time_input_qty_produksi'] = date('H:i:s', strtotime((string)$data['time_input_qty_produksi']));
        }

        return $data;
    }

    /**
     * Pick a value from source based on multiple aliases.
     */
    private function pick(array $source, array $aliases)
    {
        foreach ($aliases as $alias) {
            $normalizedAlias = strtoupper(trim($alias));
            if (array_key_exists($normalizedAlias, $source)) {
                return $source[$normalizedAlias];
            }
        }
        return null;
    }

    /**
     * Parse numeric values handling various formats (HH:MM, commas, scientific notation).
     */
    private function parseNumeric($val): ?float
    {
        // HH:MM format
        if (is_string($val) && strpos($val, ':') !== false) {
            $parts = explode(':', $val);
            if (count($parts) >= 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                return (float)$parts[0] + ((float)$parts[1] / 60);
            }
            return null;
        }

        $cleanStr = str_replace('%', '', trim((string) $val));

        // Regional settings: 4,460.00 vs 4.460,00
        $lastComma = strrpos($cleanStr, ',');
        $lastDot = strrpos($cleanStr, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $cleanStr = str_replace('.', '', $cleanStr);
                $cleanStr = str_replace(',', '.', $cleanStr);
            } else {
                $cleanStr = str_replace(',', '', $cleanStr);
            }
        } elseif ($lastComma !== false) {
            $cleanStr = str_replace(',', '.', $cleanStr);
        }

        // Handle scientific notation and clean characters
        $cleanVal = preg_replace('/[^0-9.\-Ee\+]/', '', $cleanStr);

        // Prevent multi-dots
        $parts = explode('.', $cleanVal);
        if (count($parts) > 2) {
            $cleanVal = str_replace('.', '', $cleanStr);
        }

        return is_numeric($cleanVal) ? (float) $cleanVal : null;
    }
}
