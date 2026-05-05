@extends('layouts.app')

@section('title', 'Dashboard — Daily Loading Machine')

@section('styles')
<style>
    .date-mode-btn {
        border-radius: 6px;
        font-size: 0.85rem;
        padding: 4px 12px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        transition: all 0.15s ease;
    }
    .date-mode-btn.active, .date-mode-btn:hover {
        background: #1e3a5f;
        color: #fff;
        border-color: #1e3a5f;
    }
    .filter-bar {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        padding: 14px 20px;
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }
    .filter-bar label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .loading-pct-good   { color: #198754; font-weight: 700; }
    .loading-pct-medium { color: #fd7e14; font-weight: 700; }
    .loading-pct-low    { color: #dc3545; font-weight: 700; }
    #wrapMachineTypeFilter.machine-type-locked {
        opacity: 0.55;
    }
    #wrapMachineTypeFilter.machine-type-locked select {
        cursor: not-allowed;
    }

    .filter-pill-select-wrap {
        position: relative;
        display: inline-block;
        border-radius: 12px;
        background: #4f6cf7;
        box-shadow: 0 3px 14px rgba(79, 108, 247, 0.45);
        min-width: 8.5rem;
        max-width: 12rem;
    }
    .filter-pill-select-wrap.filter-pill-select-wrap--wide {
        min-width: 10rem;
        max-width: 20rem;
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
    .filter-pill-select:disabled {
        cursor: not-allowed;
        opacity: 0.88;
    }
    .filter-pill-select option,
    .filter-pill-select optgroup {
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
<a class="page-heading-link" href="{{ route('loading.index', request()->query()) }}" title="Kembali ke Home">
    <div class="page-heading-bar d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-line-fill text-primary"></i>
        <h1>Daily Loading Machine</h1>
    </div>
</a>
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4">

    <div class="filter-bar" id="filterBar">

        {{-- Toggle Per Hari / Per Bulan --}}
        <div class="d-flex align-items-center gap-1">
            <button type="button" id="btnDaily"
                class="date-mode-btn {{ $dateMode === 'daily' ? 'active' : '' }}">
                <i class="bi bi-calendar-day me-1"></i>Per Hari
            </button>
            <button type="button" id="btnMonthly"
                class="date-mode-btn {{ $dateMode === 'monthly' ? 'active' : '' }}">
                <i class="bi bi-calendar-month me-1"></i>Per Bulan
            </button>
        </div>

        <div id="wrapDaily" class="{{ $dateMode === 'daily' ? '' : 'd-none' }} d-flex align-items-center gap-2 flex-wrap">
            <label for="inputDate" class="mb-0 fw-semibold text-muted" style="font-size: 0.95rem;">Tanggal:</label>
            <label class="date-pill mb-0">
                <div class="date-pill-face">
                    <span id="displayDateFormatted" class="text-nowrap">{{ \Carbon\Carbon::parse($selectedDate)->format('d-M-Y') }}</span>
                    <i class="bi bi-calendar3 date-pill-icon" aria-hidden="true"></i>
                </div>
                <input type="date" id="inputDate" class="date-pill-input" value="{{ $selectedDate }}" aria-label="Pilih tanggal">
            </label>
        </div>

        <div id="wrapMonthly" class="{{ $dateMode === 'monthly' ? '' : 'd-none' }} d-flex align-items-center gap-2 flex-wrap">
            <label for="inputMonth" class="mb-0 fw-semibold text-muted" style="font-size: 0.95rem;">Bulan:</label>
            <label class="date-pill mb-0">
                <div class="date-pill-face">
                    <span id="displayMonthFormatted" class="text-nowrap">{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('M-Y') }}</span>
                    <i class="bi bi-calendar3 date-pill-icon" aria-hidden="true"></i>
                </div>
                <input type="month" id="inputMonth" class="date-pill-input" value="{{ $selectedMonth }}" aria-label="Pilih bulan">
            </label>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap ms-md-2" id="wrapMachineTypeFilter">
            <span class="mb-0 fw-semibold text-muted" style="font-size: 0.95rem;">Machine Type:</span>
            <div class="filter-pill-select-wrap filter-pill-select-wrap--wide">
                <select id="selectMachineType" class="filter-pill-select" aria-label="Pilih Machine Type" title="">
                    <option value="" {{ $selectedMachineType === '' ? 'selected' : '' }}>— Semua —</option>
                    @foreach($machineTypes as $type)
                        <option value="{{ $type }}" {{ $selectedMachineType === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down filter-pill-select-icon" aria-hidden="true"></i>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="mb-0 fw-semibold text-muted" style="font-size: 0.95rem;">Machine No:</span>
            <div class="filter-pill-select-wrap">
                <select id="selectMachineNo" class="filter-pill-select" aria-label="Pilih Machine No">
                    <option value="">— Semua —</option>
                    @foreach($machineList as $no => $type)
                        <option value="{{ $no }}" {{ $selectedMachineNo === $no ? 'selected' : '' }}>
                            {{ $no }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down filter-pill-select-icon" aria-hidden="true"></i>
            </div>
        </div>

        <button type="button" id="btnApplyFilter" class="btn btn-primary btn-sm ms-auto" style="border-radius:8px;">
            <i class="bi bi-funnel me-1"></i>Terapkan
        </button>
    </div>


    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <div class="card-header py-3 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-bar-chart me-2"></i>% Loading per Mesin</span>
                        <small class="text-muted" id="chartSubtitle">
                            @if($dateMode === 'monthly')
                                Bulan: {{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('M-Y') }}
                            @else
                                Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d-M-Y') }}
                            @endif
                        </small>
                    </div>
                    <small class="text-muted mt-1" style="font-size: 0.78rem;">100% = 7×2 jam (2 Shift)</small>
                </div>
                <div class="card-body">
                    <div id="chartEmpty" class="{{ count($chartData['labels']) > 0 ? 'd-none' : '' }} text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">Tidak ada data untuk ditampilkan</p>
                    </div>
                    <div style="overflow-x: auto; overflow-y: hidden;">
                        <div id="chartWrapper" style="position:relative; height:320px; width:100%; min-width: {{ count($chartData['labels']) * 50 }}px;"
                             class="{{ count($chartData['labels']) === 0 ? 'd-none' : '' }}">
                            <div id="chartLoading"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card chart-card mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="bi bi-table me-2"></i>Daftar Mesin</span>
            <input type="text" id="tableSearch" class="form-control form-control-sm"
                placeholder="Cari mesin..." style="max-width:220px; border-radius:8px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 520px; overflow: auto;">
                <table class="table table-production table-striped table-hover mb-0" style="min-width: 600px;">
                    <thead style="position: sticky; top: 0; z-index: 2; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <tr>    
                            <th>Machine No</th>
                            <th>Date</th>
                            <th>Work Time (M)</th>
                            <th>Durasi Total (M)</th>
                            <th>Work Time Eff (M)</th>
                            <th>% Loading</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($records as $row)
                            @php
                                $pct = (float)($row['% Loading'] ?? 0) * 100;
                                $pctClass = $pct >= 80
                                    ? 'loading-pct-good'
                                    : ($pct >= 50 ? 'loading-pct-medium' : 'loading-pct-low');
                                $fmtNum = fn($v) => $v !== null
                                    ? rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.')
                                    : '-';
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $row['Machine No'] ?? '-' }}</td>
                                <td>{{ !empty($row['Date']) ? \Carbon\Carbon::parse($row['Date'])->format('d-M-Y') : '-' }}</td>
                                <td>{{ $fmtNum($row['Work Time Mc'] ?? null) }}</td>
                                <td>{{ $fmtNum($row['SumOfDurasi M Total'] ?? null) }}</td>
                                <td>{{ $fmtNum($row['SumOfWork Time Eff M'] ?? null) }}</td>
                                <td><span class="{{ $pctClass }}">{{ number_format($pct, 1, ',', '.') }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                                    <p class="mt-2 mb-0">Tidak ada data untuk tanggal ini</p>
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
    let chartLoading = null;
    let currentMode  = '{{ $dateMode }}';
    let savedMachineTypeWhenLocked = '';

    const initialChartData = @json($chartData);

    function renderChart(chartData) {
        const wrapper = document.getElementById('chartWrapper');
        const emptyEl = document.getElementById('chartEmpty');

        if (!chartData.labels || chartData.labels.length === 0) {
            wrapper.classList.add('d-none');
            emptyEl.classList.remove('d-none');
            return;
        }

        wrapper.classList.remove('d-none');
        emptyEl.classList.add('d-none');

        if (chartLoading) chartLoading.destroy();

        const options = {
            series: [{
                name: '% Loading',
                type: 'column',
                data: chartData.values
            }, {
                name: 'Target 100%',
                type: 'line',
                data: chartData.labels.map(() => 100)
            }],
            chart: {
                height: 300,
                type: 'line',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                    },
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
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
                    shadeIntensity: 0.25,
                    gradientToColors: undefined,
                    inverseColors: true,
                    opacityFrom: 0.85,
                    opacityTo: 0.85,
                    stops: [50, 0, 100]
                },
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
                    fontSize: '12px',
                    colors: ["#304758"]
                }
            },
            labels: chartData.labels,
            xaxis: {
                type: 'category',
                tooltip: {
                    enabled: false
                }
            },
            yaxis: [{
                title: {
                    text: '% Loading',
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
            markers: {
                size: 0
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

        chartLoading = new ApexCharts(document.querySelector("#chartLoading"), options);
        chartLoading.render();
    }

    function updateTable(rows) {
        const tbody = document.getElementById('tableBody');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">Tidak ada data untuk filter yang dipilih</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = rows.map(row => {
            const pct = parseFloat(row['% Loading'] ?? 0) * 100;
            const pctClass = pct >= 80 ? 'loading-pct-good'
                           : pct >= 50 ? 'loading-pct-medium'
                                       : 'loading-pct-low';
            const pctStr = pct.toFixed(1).replace('.', ',') + '%';
            const fmt = v => v != null ? parseFloat(v).toString() : '-';
            return `<tr>
                <td class="fw-medium">${row['Machine No'] ?? '-'}</td>
                <td>${formatIsoDateToDMY(row['Date'] ?? '')}</td>
                <td>${fmt(row['Work Time Mc'])}</td>
                <td>${fmt(row['SumOfDurasi M Total'])}</td>
                <td>${fmt(row['SumOfWork Time Eff M'])}</td>
                <td><span class="${pctClass}">${pctStr}</span></td>
            </tr>`;
        }).join('');
    }

    function syncDateMonthLabels() {
        const d = document.getElementById('inputDate').value;
        const m = document.getElementById('inputMonth').value;
        const elD = document.getElementById('displayDateFormatted');
        const elM = document.getElementById('displayMonthFormatted');
        if (elD && d) elD.textContent = formatIsoDateToDMY(d);
        if (elM && m) elM.textContent = formatIsoMonthToMY(m);
    }

    function updateSubtitle() {
        const el = document.getElementById('chartSubtitle');
        if (currentMode === 'monthly') {
            el.textContent = 'Bulan: ' + formatIsoMonthToMY(document.getElementById('inputMonth').value);
        } else {
            el.textContent = 'Tanggal: ' + formatIsoDateToDMY(document.getElementById('inputDate').value);
        }
    }

    function syncMachineTypeLock() {
        const noEl = document.getElementById('selectMachineNo');
        const typeEl = document.getElementById('selectMachineType');
        const wrap = document.getElementById('wrapMachineTypeFilter');
        const specific = noEl.value !== '';

        if (specific) {
            if (!typeEl.disabled && typeEl.value) {
                savedMachineTypeWhenLocked = typeEl.value;
            }
            typeEl.value = '';
            typeEl.disabled = true;
            typeEl.title = 'Filter tipe dinonaktifkan saat memilih mesin spesifik.';
            wrap.classList.add('machine-type-locked');
        } else {
            typeEl.disabled = false;
            typeEl.title = '';
            wrap.classList.remove('machine-type-locked');
            if (savedMachineTypeWhenLocked) {
                const hasOpt = [...typeEl.options].some(o => o.value === savedMachineTypeWhenLocked);
                if (hasOpt) typeEl.value = savedMachineTypeWhenLocked;
                savedMachineTypeWhenLocked = '';
            }
        }
    }

    async function fetchData() {
        showLoading();

        const mode        = currentMode;
        const date        = document.getElementById('inputDate').value;
        const month       = document.getElementById('inputMonth').value;
        const machineNo   = document.getElementById('selectMachineNo').value;
        const machineType = machineNo ? '' : document.getElementById('selectMachineType').value;

        const params = new URLSearchParams({ mode, date, month, machine_type: machineType, machine_no: machineNo });

        try {
            const resp = await fetch(`/dashboard/loading/data?${params}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });

            if (!resp.ok) throw new Error('Gagal memuat data');

            const data = await resp.json();

            renderChart(data.chart);
            updateTable(data.table);
            updateSubtitle();

            const urlParams = new URLSearchParams({ mode, date, month, machine_type: machineType, machine_no: machineNo });
            history.pushState(null, '', `/dashboard/loading?${urlParams}`);

        } catch (err) {
            console.error('Error fetching loading data:', err);
            alert('Gagal memuat data. Silakan coba lagi.');
        } finally {
            hideLoading();
        }
    }

    function setMode(mode) {
        currentMode = mode;

        document.getElementById('btnDaily').classList.toggle('active', mode === 'daily');
        document.getElementById('btnMonthly').classList.toggle('active', mode === 'monthly');
        document.getElementById('wrapDaily').classList.toggle('d-none', mode !== 'daily');
        document.getElementById('wrapMonthly').classList.toggle('d-none', mode !== 'monthly');
    }

    document.getElementById('btnDaily').addEventListener('click', () => setMode('daily'));
    document.getElementById('btnMonthly').addEventListener('click', () => setMode('monthly'));

    document.getElementById('btnApplyFilter').addEventListener('click', fetchData);

    document.getElementById('inputDate').addEventListener('change', syncDateMonthLabels);
    document.getElementById('inputMonth').addEventListener('change', syncDateMonthLabels);

    document.getElementById('selectMachineNo').addEventListener('change', syncMachineTypeLock);

    document.getElementById('tableSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        syncMachineTypeLock();
        renderChart(initialChartData);
    });
</script>
@endsection
