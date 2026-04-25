<?php

namespace Database\Seeders;

use App\Models\ProductionRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data hari ini (agar langsung tampil di default dashboard)
        $today = Carbon::today()->format('Y-m-d');
        
        // Buat 32 record untuk 32 mesin berbeda hari ini
        for ($i = 1; $i <= 32; $i++) {
            $machineNo = 'F' . str_pad($i, 2, '0', STR_PAD_LEFT);
            ProductionRecord::factory()->create([
                'date' => $today,
                'machine_no' => $machineNo,
            ]);
        }

        // Data beberapa hari ke belakang (random machine)
        ProductionRecord::factory()->count(80)->create();
    }
}
