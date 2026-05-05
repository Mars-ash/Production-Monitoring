@extends('layouts.app')

@section('title', 'Dashboard — Daily Live Production')

@section('styles')
<style>
    .filter-pill-select-wrap {
        position: relative;
        display: inline-block;
        border-radius: 12px;
        background: #4f6cf7;
        box-shadow: 0 3px 14px rgba(79, 108, 247, 0.45);
        min-width: 8.5rem;
        max-width: 14rem;
    }
    .filter-pill-select-wrap:hover {
        filter: brightness(1.06);
    }
    .filter-pill-select-wrap:focus-within {
        outline: 3px solid rgba(255, 255, 255, 0.95);
        outline-offset: 2px;
    }
    .filter-pill-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        max-width: 100%;
        background: transparent;
        border: 0;
        color: #fff;
        font-weight: 700;
        font-size: 1.15rem;
        line-height: 1.25;
        letter-spacing: 0.02em;
        padding: 0.65rem 2.35rem 0.65rem 1.1rem;
        min-height: 3rem;
        cursor: pointer;
        border-radius: 12px;
        box-sizing: border-box;
    }
    .filter-pill-select option {
        color: #1a1d23;
        background: #fff;
        font-weight: 600;
    }
    .filter-pill-select-icon {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #fff;
        font-size: 1.1rem;
        opacity: 0.95;
        line-height: 1;
    }
</style>
@endsection

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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="form-label mb-0 fw-semibold text-muted">Status:</span>
                    <div class="filter-pill-select-wrap">
                        <select class="filter-pill-select" id="statusFilter" aria-label="Pilih status">
                            <option value="" {{ $selectedStatus === '' ? 'selected' : '' }}>Semua</option>
                            <option value="RUN" {{ $selectedStatus === 'RUN' ? 'selected' : '' }}>RUN</option>
                            <option value="FINISH" {{ $selectedStatus === 'FINISH' ? 'selected' : '' }}>FINISH</option>
                        </select>
                        <i class="bi bi-chevron-down filter-pill-select-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="form-label mb-0 fw-semibold text-muted">Machine Type:</span>
                    <div class="filter-pill-select-wrap" style="min-width: 12rem;">
                        <select class="filter-pill-select" id="machineTypeFilter" aria-label="Pilih machine type">
                            <option value="" {{ ($selectedMachineType ?? '') === '' ? 'selected' : '' }}>Semua</option>
                            <option value="Stamping" {{ ($selectedMachineType ?? '') === 'Stamping' ? 'selected' : '' }}>Stamping</option>
                            <option value="Non Stamping" {{ ($selectedMachineType ?? '') === 'Non Stamping' ? 'selected' : '' }}>Non Stamping</option>
                        </select>
                        <i class="bi bi-chevron-down filter-pill-select-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card chart-card h-100">
                <div class="card-header py-3">
                    <i class="bi bi-bar-chart me-2"></i>Produktivitas per Mesin
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto; overflow-y: hidden;">
                        <div id="chartProductivityWrapper" style="position: relative; height: 350px; width: 100%;">
                            <div id="chartProductivity"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            <div class="table-responsive" style="max-height: 520px; overflow: auto;">
                <table class="table table-production table-striped table-hover mb-0" style="min-width: 700px;">
                    <thead style="position: sticky; top: 0; z-index: 2; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <tr>
                            <th>Machine No</th>
                            <th>Part Name</th>
                            <th>Time Start</th>
                            <th id="timeColumnHeader">{{ $selectedStatus === 'FINISH' ? 'Time Finish' : 'Time Input' }}</th>
                            <th>Qty</th>
                            <th>Productivity</th>
                            <th>Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($records as $record)
                            <tr onclick="window.location='{{ route('production.show', $record->id) }}'">
                                <td class="fw-medium">{{ $record->machine_no }}</td>
                                <td>{{ $record->part_name ?? '-' }}</td>
                                <td>{{ $record->time_start ? \Carbon\Carbon::parse($record->time_start)->format('H:i') : '-' }}</td>
                                @if(strtoupper(trim($record->finish ?? '')) === 'FINISH')
                                    <td>{{ $record->time_finish ? \Carbon\Carbon::parse($record->time_finish)->format('H:i') : '-' }}</td>
                                @else
                                    <td>{{ $record->time_input_qty_produksi ? \Carbon\Carbon::parse($record->time_input_qty_produksi)->format('H:i') : '-' }}</td>
                                @endif
                                <td>{{ $record->qty_proses !== null ? number_format($record->qty_proses, 0, ',', '.') : '-' }}</td>
                                <td>{{ $record->productivity !== null ? number_format($record->productivity * 100, 0, ',', '.') . '%' : '-' }}</td>
                                <td>{{ $record->target_productivity !== null ? number_format($record->target_productivity * 100, 0, ',', '.') . '%' : '-' }}</td>
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
                                <td colspan="8" class="text-center text-muted py-5">
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
    let chartProductivity = null;

    const chartColors = [
        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1',
        '#0dcaf0', '#fd7e14', '#20c997', '#d63384', '#6610f2',
    ];

    function renderCharts(data) {
        const prodLabels = Object.keys(data.charts.productivityByMachine);
        const prodValues = Object.values(data.charts.productivityByMachine);

        const chartWrapper = document.getElementById('chartProductivityWrapper');
        if (chartWrapper) {
            chartWrapper.style.minWidth = (prodLabels.length * 50) + 'px';
        }

        if (chartProductivity) chartProductivity.destroy();

        const options = {
            series: [{
                name: 'Produktivitas (%)',
                type: 'column',
                data: prodValues
            }, {
                name: 'Target 100%',
                type: 'line',
                data: prodLabels.map(() => 100)
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: [0, 2],
                curve: 'smooth',
                dashArray: [0, 5]
            },
            colors: ['#0d6efd', '#dc3545'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: "vertical",
                    opacityFrom: 0.85,
                    opacityTo: 0.85,
                    stops: [50, 0, 100]
                }
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%',
                    borderRadius: 6,
                    dataLabels: {
                        position: 'top',
                    }
                }
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [0],
                formatter: function (val) {
                    return val + "%";
                },
                offsetY: -20,
                style: {
                    fontSize: '11px',
                    colors: ["#304758"]
                }
            },
            labels: prodLabels,
            xaxis: {
                type: 'category'
            },
            yaxis: [{
                title: {
                    text: 'Produktivitas (%)',
                },
                max: 120,
                labels: {
                    formatter: function (val) {
                        return val.toFixed(0) + "%";
                    }
                }
            }],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return y.toFixed(1) + "%";
                        }
                        return y;
                    }
                }
            }
        };

        chartProductivity = new ApexCharts(document.querySelector("#chartProductivity"), options);
        chartProductivity.render();
    }

    function updateStats(stats) {
        document.getElementById('statMachines').textContent = formatNumber(stats.active_machines);
        document.getElementById('statProductivity').textContent = formatPercent(stats.avg_productivity * 100);
    }

    function updateTable(tableData) {
        const tbody = document.getElementById('tableBody');
        const status = getSelectedStatus();
        const headerEl = document.getElementById('timeColumnHeader');
        if (headerEl) {
            headerEl.textContent = status === 'FINISH' ? 'Time Finish' : 'Time Input';
        }

        if (!tableData || tableData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
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
                <td>${row.time_start ? row.time_start.slice(0, 5) : '-'}</td>
                <td>${row.finish && row.finish.toUpperCase().trim() === 'FINISH' ? (row.time_finish ? row.time_finish.slice(0, 5) : '-') : (row.time_input_qty_produksi ? row.time_input_qty_produksi.slice(0, 5) : '-')}</td>
                <td>${row.qty_proses !== null ? Number(row.qty_proses).toLocaleString('id-ID') : '-'}</td>
                <td>${row.productivity !== null ? formatPercent(row.productivity * 100) : '-'}</td>
                <td>${row.target_productivity !== null ? formatPercent(row.target_productivity * 100) : '-'}</td>
                <td>
                    ${row.is_on_target
                        ? '<span class="badge badge-on-target"><i class="bi bi-check-circle me-1"></i>On Target</span>'
                        : '<span class="badge badge-below-target"><i class="bi bi-exclamation-circle me-1"></i>Below Target</span>'
                    }
                </td>
            </tr>
        `).join('');
    }

    function getSelectedStatus() {
        const el = document.getElementById('statusFilter');
        return el ? el.value : '';
    }

    function getSelectedMachineType() {
        const el = document.getElementById('machineTypeFilter');
        return el ? el.value : '';
    }

    async function fetchData(date) {
        showLoading();

        const status = getSelectedStatus();
        const machineType = getSelectedMachineType();
        const params = new URLSearchParams({ date });
        params.set('status', status);
        params.set('machine_type', machineType);

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

    function pushDashboardUrl(date) {
        const status = getSelectedStatus();
        const machineType = getSelectedMachineType();
        const params = new URLSearchParams({ date });
        params.set('status', status);
        params.set('machine_type', machineType);
        history.pushState(null, '', `/dashboard?${params}`);
    }

    function syncDateDisplayFormatted() {
        const sp = document.getElementById('dateDisplayFormatted');
        const v = document.getElementById('datePicker').value;
        if (sp && v) sp.textContent = formatIsoDateToDMY(v);
    }

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

    document.getElementById('machineTypeFilter').addEventListener('change', function () {
        const date = document.getElementById('datePicker').value;
        if (date) {
            pushDashboardUrl(date);
            fetchData(date);
        }
    });

    document.getElementById('tableSearch').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tableBody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        fetchData(document.getElementById('datePicker').value || '{{ $selectedDate }}');
    });
</script>
@endsection
