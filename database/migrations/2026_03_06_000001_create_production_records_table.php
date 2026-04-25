<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel production_records — menyimpan data produksi harian
     * yang disinkronkan dari Microsoft Access.
     */
    public function up(): void
    {
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();

            // Identifikasi mesin & waktu
            $table->string('machine_no', 50)->index();
            $table->date('date')->index();
            $table->time('time_start')->nullable();
            $table->time('time_finish')->nullable();

            // Operator & customer
            $table->string('operator', 100)->nullable();
            $table->string('customer', 100)->nullable()->index();

            // Part info
            $table->string('part_no', 50)->nullable();
            $table->string('part_name', 150)->nullable();
            $table->string('model', 100)->nullable();

            // Proses
            $table->string('process', 50)->nullable();
            $table->string('process_name', 150)->nullable();

            // Metrik produksi
            $table->decimal('ppm', 10, 2)->nullable()->comment('Parts per minute');
            $table->time('time_input_qty_produksi')->nullable();
            $table->decimal('work_time_eff_h', 10, 2)->nullable()->comment('Efisiensi waktu kerja (jam)');
            $table->decimal('work_time_eff_m', 10, 2)->nullable()->comment('Efisiensi waktu kerja (menit)');
            $table->integer('qty_target')->nullable();
            $table->integer('qty_proses')->nullable()->comment('Qty actual');
            $table->decimal('productivity', 8, 2)->nullable()->comment('Produktivitas (%)');
            $table->decimal('target_productivity', 8, 2)->nullable()->comment('Target produktivitas (%)');

            // Durasi & status
            $table->decimal('durasi_m_total', 10, 2)->nullable()->comment('Durasi total (menit)');
            $table->string('finish', 50)->nullable();
            $table->string('id_pdr', 100)->nullable();

            // Maintenance & problem
            $table->text('dies_problem')->nullable();
            $table->text('preventive_mtn')->nullable();

            $table->timestamps();

            // Composite unique: mencegah duplikasi saat upsert
            $table->unique(
                ['machine_no', 'date', 'time_start', 'part_no'],
                'uq_production_record'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_records');
    }
};
