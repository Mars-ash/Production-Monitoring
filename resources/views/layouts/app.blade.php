<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daily Live Production')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --dark-bg: #1a1d23;
            --card-bg: #212529;
            --text-muted: #adb5bd;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #1e3a5f 0%, #0d253f 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        .chart-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .chart-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e9ecef;
            font-weight: 700;
            font-size: 1.25rem;
            line-height: 1.35;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .chart-card .card-header i.bi {
            font-size: 1.2rem;
            vertical-align: -0.1em;
        }

        .chart-card .card-header small,
        .chart-card .card-header .text-muted {
            font-size: 1.05rem;
            font-weight: 600;
            color: #495057 !important;
        }

        /* Pill tanggal — biru, teks putih, ikon kalender (mudah dibaca) */
        label.date-pill {
            position: relative;
            display: inline-block;
            min-width: 11.5rem;
            max-width: 100%;
            cursor: pointer;
            vertical-align: middle;
        }

        .date-pill-face {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: #4f6cf7;
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            line-height: 1.25;
            letter-spacing: 0.02em;
            border-radius: 12px;
            padding: 0.7rem 1rem 0.7rem 1.15rem;
            box-shadow: 0 3px 14px rgba(79, 108, 247, 0.45);
            user-select: none;
        }

        .date-pill-icon {
            font-size: 1.35rem;
            opacity: 0.95;
            flex-shrink: 0;
        }

        /* Input menutupi pill; area klik native date sering kecil — perluas + label sebagai cadangan */
        .date-pill-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            min-height: 3rem;
            margin: 0;
            padding: 0;
            border: 0;
            opacity: 0;
            cursor: pointer;
            font-size: 16px; /* cegah zoom iOS */
            z-index: 2;
            box-sizing: border-box;
        }

        .date-pill-input::-webkit-calendar-picker-indicator {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            cursor: pointer;
            opacity: 0;
        }

        label.date-pill:hover .date-pill-face {
            filter: brightness(1.06);
        }

        label.date-pill:focus-within .date-pill-face {
            outline: 3px solid rgba(255, 255, 255, 0.95);
            outline-offset: 2px;
        }

        .table-production thead th {
            background-color: #1e3a5f;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .table-production tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .table-production tbody tr:hover {
            background-color: #e8f0fe !important;
        }

        .badge-on-target {
            background-color: #198754;
            font-size: 0.85rem;
            padding: 4px 10px;
        }

        .badge-below-target {
            background-color: #dc3545;
            font-size: 0.85rem;
            padding: 4px 10px;
        }

        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        @media (max-width: 768px) {
            .stat-card .stat-value {
                font-size: 1.4rem;
            }

            .chart-card {
                margin-bottom: 1rem;
            }
        }

        .page-heading-bar {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            padding: 1rem 1.25rem;
            border-left: 4px solid #1e3a5f;
        }

        .page-heading-bar h1 {
            font-size: 1.85rem;
            font-weight: 700;
            color: #1a1d23;
            margin: 0;
            letter-spacing: 0.02em;
            line-height: 1.25;
        }

        .page-heading-bar > i.bi {
            font-size: 1.65rem;
        }

        /* Page heading sticky under navbar */
        .page-heading-sticky {
            position: sticky;
            top: 0;
            z-index: 1019; /* below bootstrap sticky navbar (1020) */
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            background: #f0f2f5;
        }

        /* Make page heading act like a button/link */
        .page-heading-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .page-heading-link .page-heading-bar {
            transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease;
        }

        .page-heading-link:hover .page-heading-bar {
            filter: brightness(1.01);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .page-heading-link:active .page-heading-bar {
            transform: translateY(1px);
        }

        .page-heading-link:focus-visible .page-heading-bar {
            outline: 3px solid rgba(13, 110, 253, 0.35);
            outline-offset: 2px;
        }
    </style>
    {{-- CSS per halaman: harus di LUAR <style> utama agar tidak nested (invalid HTML) --}}
    @yield('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            @php
                $isLoadingDashboard = request()->routeIs('loading.*');
            @endphp

            {{-- Tombol switch dashboard (kiri navbar, tetap terlihat) --}}
            @if($isLoadingDashboard)
                <a href="{{ route('dashboard') }}"
                   class="btn btn-sm d-flex align-items-center gap-1 me-2 my-1 text-nowrap"
                   title="Beralih ke Daily Live Production"
                   id="btnSwitchDashboard"
                   style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 8px; padding: 4px 10px; font-size: 0.8rem; transition: background 0.2s;">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Live Production</span>
                </a>
            @else
                <a href="{{ route('loading.index') }}"
                   class="btn btn-sm d-flex align-items-center gap-1 me-2 my-1 text-nowrap"
                   title="Beralih ke Daily Loading Machine"
                   id="btnSwitchDashboard"
                   style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 8px; padding: 4px 10px; font-size: 0.8rem; transition: background 0.2s;">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Loading Machine</span>
                </a>
            @endif

            <button class="navbar-toggler ms-auto ms-lg-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse flex-grow-1" id="navbarContent">
                <div class="d-flex align-items-center gap-3 ms-lg-auto mt-2 mt-lg-0 pb-2 pb-lg-0">
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('users.index') }}" class="text-light text-decoration-none dropdown-item-custom me-2" style="font-size: 0.9rem; opacity: 0.9;">
                            <i class="bi bi-people-fill me-1"></i>Manajemen User
                        </a>
                    @endif
                    <span class="text-light opacity-75" style="font-size: 0.85rem;">
                        <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->username }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>


    <!-- Flash Messages -->
    @if(session('success'))
        <div class="container-fluid px-3 px-lg-4 mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container-fluid px-3 px-lg-4 mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <p class="mt-3 text-muted fw-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Content -->
    <main class="py-3 py-lg-4">
        @hasSection('page_heading')
            <div class="page-heading-sticky">
                <div class="container-fluid px-3 px-lg-4">
                @yield('page_heading')
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous"></script>

    <script>
        // CSRF token untuk AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Fungsi loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay')?.classList.add('active');
        }
        function hideLoading() {
            document.getElementById('loadingOverlay')?.classList.remove('active');
        }

        // Formatter angka Indonesia (separator ribuan: titik, desimal: koma)
        function formatNumber(num) {
            if (num === null || num === undefined) return '-';
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function formatPercent(num) {
            if (num === null || num === undefined) return '-';
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num) + '%';
        }

        const FORMAT_DATE_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        /** YYYY-MM-DD → 24-Apr-2026 */
        function formatIsoDateToDMY(iso) {
            if (iso == null || iso === '') return '-';
            const s = String(iso).trim();
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (!m) return s;
            const mi = parseInt(m[2], 10) - 1;
            const mon = FORMAT_DATE_MONTHS[mi] || m[2];
            return `${parseInt(m[3], 10)}-${mon}-${m[1]}`;
        }

        /** YYYY-MM → Apr-2026 */
        function formatIsoMonthToMY(ym) {
            if (ym == null || ym === '') return '-';
            const s = String(ym).trim();
            const match = s.match(/^(\d{4})-(\d{2})/);
            if (!match) return s;
            const mi = parseInt(match[2], 10) - 1;
            const mon = FORMAT_DATE_MONTHS[mi] || match[2];
            return `${mon}-${match[1]}`;
        }
    </script>

    @yield('scripts')
</body>
</html>
