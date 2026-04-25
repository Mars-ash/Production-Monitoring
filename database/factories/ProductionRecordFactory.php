<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionRecord>
 */
class ProductionRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $timeStartHour = fake()->numberBetween(7, 15);
        $timeStart = sprintf('%02d:%02d:00', $timeStartHour, fake()->numberBetween(0, 59));
        $timeFinish = sprintf('%02d:%02d:00', $timeStartHour + fake()->numberBetween(1, 4), fake()->numberBetween(0, 59));
        
        $qtyProses = fake()->numberBetween(20000, 30000);
        $qtyTarget = fake()->numberBetween(20000, 30000);
        
        $productivity = ($qtyTarget > 0) ? round($qtyProses / $qtyTarget, 2) : 0;
        
        return [
            'machine_no' => 'F' . str_pad(fake()->numberBetween(1, 32), 2, '0', STR_PAD_LEFT),
            'date' => fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
            'time_start' => $timeStart,
            'operator' => fake()->randomElement(['Bagas', 'Rudi', 'Andi', 'Budi']),
            'part_no' => fake()->randomElement(['90302-KWW-A000-T', '12345-KZR-B000-T', '90302-K1AA-A000']),
            'part_name' => fake()->randomElement(['NUT SPRING 4 MM "T"', 'BOLT FLANGE 6X12', 'WASHER PLAIN 6MM']),
            'model' => fake()->randomElement(['KWW', 'KZR', 'K1AA']),
            'process' => fake()->randomElement(['F1', 'F2', 'A1', 'A2']),
            'qty_target' => $qtyTarget,
            'qty_proses' => $qtyProses,
            'finish' => fake()->randomElement(['RUN', 'STOP', 'DELAYED']),
            'customer' => fake()->randomElement(['MWT', 'Astra', 'Honda']),
            'process_name' => fake()->randomElement(['Forming', 'Stamping', 'Assembly', 'Inspection']),
            'time_finish' => $timeFinish,
            'durasi_m_total' => fake()->randomFloat(0, 200, 480),
            'dies_problem' => fake()->randomElement(['0', '1', '2']), // To represent issues or 0 for none
            'time_input_qty_produksi' => '12:00:00',
            'productivity' => $productivity,
            'target_productivity' => 100.00,
            'work_time_eff_h' => fake()->randomFloat(2, 5, 8),
            'work_time_eff_m' => fake()->randomFloat(2, 0, 60), // Alternatively, to match "12:00 AM", we can't easily put strings in a decimal. Let's keep it decimal.
            'ppm' => fake()->randomElement([90, 85, 95]),
            'preventive_mtn' => fake()->randomElement(['TRUE', 'FALSE']),
            'id_pdr' => fake()->randomNumber(4, true),
        ];
    }
}
