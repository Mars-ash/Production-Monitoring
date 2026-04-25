@extends('layouts.app')

@section('title', 'Dashboard — Daily Live Production')

@section('page_heading')
<a class="page-heading-link" href="{{ route('dashboard', array_filter(['date' => request('date'), 'status' => request('status')])) }}" title="Kembali ke Home">
    <div class="page-heading-bar d-flex align-items-center gap-2">
        <i class="bi bi-gear-fill text-primary"></i>
        <h1>Daily Live Production</h1>
    </div>
</a>
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4">
    {{-- Filter tanggal & status --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="form-label mb-0 fw-semibold text-muted">Tanggal:</span>
                    <label class="date-pill mb-0">
                        <div class="date-pill-face">
                            <span id="dateDisplayFormatted" class="text-nowrap">{{ \Carbon\Carbon::parse($selectedDate)->format('d-M-Y') }}</span>
                            <i class="bi bi-calendar3 date-pill-icon" aria-hidden="true"></i>
                        </div>
                        <input
                            type="date"
                            class="date-pill-input"
                            id="datePicker"
                            value="{{ $selectedDate }}"
                            aria-label="Pilih tanggal"
                        >
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="statusFilter" class="form-label mb-0 fw-medium text-muted">Status:</label>
                    <select class="form-select" id="statusFilter" style="max-width: 200px; border-radius: 8px;">
                        <option value="" {{ $selectedStatus === '' ? 'selected' : '' }}>Semua</option>
                        <option value="RUN" {{ $selectedStatus === 'RUN' ? 'selected' : '' }}>RUN</option>
                        <option value="FINISH" {{ $selectedStatus === 'FINISH' ? 'selected' : '' }}>FINISH</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik Cards --}}
    <div class="row g-3 mb-4" id="statsCards">
        <div class="col-6 col-lg-6">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <div>
                        <div class="stat-value text-primary" id="statMachines">{{ $stats['active_machines'] }}</div>
                        <div class="stat-label">Mesin Aktif</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-6">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <div class="stat-value text-success" id="statProductivity">{{ number_format($stats['avg_productivity'] * 100, 0, ',', '.') }}%</div>
                        <div class="stat-label">Rata-rata Produktivitas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card chart-card h-100">
                <div class="card-header py-3">
                    <i class="bi bi-bar-chart me-2"></i>Produktivitas per Mesin
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="chartProductivity"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Produksi --}}
    <div class="card chart-card mb-4">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <span><i class="bi bi-table me-2"></i>Daftar Produksi</span>
            <input
                type="text"
                class="form-control form-control-sm w-100"
                id="tableSearch"
                placeholder="Cari..."
                style="max-width: 100%; border-radius: 8px;"
            >
            <style>
                @media (min-width: 768px) {
                    #tableSearch { max-width: 250px !important; }
                }
            </style>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-production table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Machine No</th>
                            <th>Part Name</th>
                            <th class="d-none d-md-table-cell">Time Start</th>
                            <th class="d-none d-md-table-cell">Time Finish</th>
                            <th>Productivity</th>
                            <th class="d-none d-md-table-cell">Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($records as $record)
                            <tr onclick="window.location='{{ route('production.show', $record->id) }}'">
                                <td class="fw-medium">{{ $record->machine_no }}</td>
                                <td>{{ $record->part_name ?? '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $record->time_start ? \Carbon\Carbon::parse($record->time_start)->format('H:i') : '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $record->time_finish ? \Carbon\Carbon::parse($record->time_finish)->format('H:i') : '-' }}</td>
                                <td>{{ $record->productivity !== null ? number_format($record->productivity * 100, 0, ',', '.') . '%' : '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $record->target_productivity !== null ? number_format($record->target_productivity * 100, 0, ',', '.') . '%' : '-' }}</td>
                                <td>
                                    @if($record->is_on_target)
                                        <span class="badge badge-on-target"><i class="bi bi-check-circle me-1"></i>On Target</span>
                                    @else
                                        <span class="badge badge-below-target"><i class="bi bi-exclamation-circle me-1"></i>Below Target</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Tidak ada data produksi untuk tanggal ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ===========================
    // Chart instances
    // ===========================
    let chartProductivity = null;

    // Warna chart
    const chartColors = [
        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1',
        '#0dcaf0', '#fd7e14', '#20c997', '#d63384', '#6610f2',
    ];

    /**
     * Inisiasi / update semua chart dengan data baru.
     */
    function renderCharts(data) {
        // ---- Chart Produktivitas per Mesin ----
        const prodLabels = Object.keys(data.charts.productivityByMachine);
        const prodValues = Object.values(data.charts.productivityByMachine);

        if (chartProductivity) chartProductivity.destroy();
        chartProductivity = new Chart(document.getElementById('chartProductivity'), {
            data: {
                labels: prodLabels,
                datasets: [
                {
                    type: 'line',
                    label: 'Target 100%',
                    data: prodLabels.map(() => 100),
                    borderColor: '#dc3545',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                    order: 1
                },
                {
                    type: 'bar',
                    label: 'Produktivitas (%)',
                    data: prodValues,
                    backgroundColor: '#0d6efd', // Uniform primary color
                    borderRadius: 6,
                    maxBarThickness: 50,
                    order: 2
                }],
            },
            plugins: [{
                id: 'barLabels',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        if (dataset.type === 'line') return;
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index] + '%';
                            let yPos = bar.y - 5;
                            ctx.fillStyle = '#212529'; // Dark text default (above bar)
                            
                            // If the bar is hitting or exceeding the top boundary, place the text INSIDE the top of the bar
                            if (yPos < 15) {
                                yPos = Math.max(bar.y, 0) + 15;
                                ctx.fillStyle = '#212529'; // Dark text inside blue bar (instead of white)
                            }
                            
                            ctx.font = 'bold 13px sans-serif';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(data, bar.x, yPos);
                        });
                    });
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 120,
                        grace: '10%',
                        ticks: { callback: v => v + '%' },
                    },
                },
            },
        });


        // Chart customer dihilangkan karena tidak ada element canvas.
    }

    /**
     * Update statistik cards.
     */
    function updateStats(stats) {
        document.getElementById('statMachines').textContent = formatNumber(stats.active_machines);
        document.getElementById('statProductivity').textContent = formatPercent(stats.avg_productivity * 100);
    }

    /**
     * Update tabel produksi.
     */
    function updateTable(tableData) {
        const tbody = document.getElementById('tableBody');

        if (!tableData || tableData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Tidak ada data produksi untuk tanggal ini</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = tableData.map(row => `
            <tr onclick="window.location='/production/${row.id}'">
                <td class="fw-medium">${row.machine_no ?? '-'}</td>
                <td>${row.part_name ?? '-'}</td>
                <td class="d-none d-md-table-cell">${row.time_start ? row.time_start.slice(0, 5) : '-'}</td>
                <td class="d-none d-md-table-cell">${row.time_finish ? row.time_finish.slice(0, 5) : '-'}</td>
                <td>${row.productivity !== null ? formatPercent(row.productivity * 100) : '-'}</td>
                <td class="d-none d-md-table-cell">${row.target_productivity !== null ? formatPercent(row.target_productivity * 100) : '-'}</td>
                <td>
                    ${row.is_on_target
                        ? '<span class="badge badge-on-target"><i class="bi bi-check-circle me-1"></i>On Target</span>'
                        : '<span class="badge badge-below-target"><i class="bi bi-exclamation-circle me-1"></i>Below Target</span>'
                    }
                </td>
            </tr>
        `).join('');
    }

    /**
     * Fetch data via AJAX dan update semua komponen.
     */
    function getSelectedStatus() {
        const el = document.getElementById('statusFilter');
        return el ? el.value : '';
    }

    async function fetchData(date) {
        showLoading();

        const status = getSelectedStatus();
        const params = new URLSearchParams({ date });
        if (status) params.set('status', status);

        try {
            const response = await fetch(`/dashboard/data?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) throw new Error('Gagal memuat data');

            const data = await response.json();

            updateStats(data.stats);
            renderCharts(data);
            updateTable(data.table);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Gagal memuat data. Silakan coba lagi.');
        } finally {
            hideLoading();
        }
    }

    // ===========================
    // Event Listeners
    // ===========================

    function pushDashboardUrl(date) {
        const status = getSelectedStatus();
        const params = new URLSearchParams({ date });
        if (status) params.set('status', status);
        history.pushState(null, '', `/dashboard?${params}`);
    }

    function syncDateDisplayFormatted() {
        const sp = document.getElementById('dateDisplayFormatted');
        const v = document.getElementById('datePicker').value;
        if (sp && v) sp.textContent = formatIsoDateToDMY(v);
    }

    // Date picker change → AJAX fetch
    document.getElementById('datePicker').addEventListener('change', function () {
        const date = this.value;
        if (date) {
            syncDateDisplayFormatted();
            pushDashboardUrl(date);
            fetchData(date);
        }
    });

    document.getElementById('statusFilter').addEventListener('change', function () {
        const date = document.getElementById('datePicker').value;
        if (date) {
            pushDashboardUrl(date);
            fetchData(date);
        }
    });

    // Table search filter
    document.getElementById('tableSearch').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // ===========================
    // Initial render charts dari data server-side
    // ===========================
    document.addEventListener('DOMContentLoaded', function () {
        // Ambil data awal via AJAX untuk render charts
        fetchData(document.getElementById('datePicker').value || '{{ $selectedDate }}');
    });
</script>
@endsection
