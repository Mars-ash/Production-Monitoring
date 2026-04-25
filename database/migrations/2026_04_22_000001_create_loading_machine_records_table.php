<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel loading_machine_records — menyimpan data loading mesin harian
     * yang disinkronkan dari Microsoft Access (Query - Daily Loading Mc).
     */
    public function up(): void
    {
        Schema::create('loading_machine_records', function (Blueprint $table) {
            $table->id();

            // Identifikasi mesin & tanggal
            $table->string('machine_no', 50)->index();
            $table->date('date')->index();

            // Waktu kerja
            $table->decimal('work_time_mc', 10, 4)->nullable()->comment('Work Time Mc');
            $table->decimal('durasi_m_total', 10, 4)->nullable()->comment('SumOfDurasi M Total');
            $table->decimal('work_time_eff_m', 10, 4)->nullable()->comment('SumOfWork Time Eff M');

            // Persentase loading (disimpan sebagai desimal 0–1 sesuai sumber Access)
            $table->decimal('loading_pct', 8, 6)->nullable()->comment('% Loading (desimal 0–1)');

            // Tipe mesin
            $table->string('machine_type_process', 100)->nullable()->index()->comment('Machine Type Process');
            $table->string('mesin_type', 100)->nullable()->index()->comment('Mesin Type');

            $table->timestamps();

            // Composite unique: satu record per mesin per hari
            $table->unique(
                ['machine_no', 'date'],
                'uq_loading_machine_record'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_machine_records');
    }
};
