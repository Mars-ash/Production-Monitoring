@extends('layouts.app')

@section('title', 'Detail Produksi — ' . $record->part_name)

@section('page_heading')
<a class="page-heading-link" href="{{ route('dashboard', ['date' => $record->date?->format('Y-m-d')]) }}" title="Kembali ke Home">
    <div class="page-heading-bar d-flex align-items-center gap-2">
        <i class="bi bi-gear-fill text-primary"></i>
        <h1>Daily Live Production</h1>
    </div>
</a>
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-info-circle me-2"></i>Detail Produksi
        </h4>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ $previousId ? route('production.show', $previousId) : '#' }}"
               class="btn btn-outline-secondary btn-sm {{ $previousId ? '' : 'disabled' }}"
               id="btn-prev-record"
               @if(!$previousId) aria-disabled="true" tabindex="-1" @endif>
                <i class="bi bi-chevron-left"></i> Prev
            </a>
            <a href="{{ $nextId ? route('production.show', $nextId) : '#' }}"
               class="btn btn-outline-secondary btn-sm {{ $nextId ? '' : 'disabled' }}"
               id="btn-next-record"
               @if(!$nextId) aria-disabled="true" tabindex="-1" @endif>
                Next <i class="bi bi-chevron-right"></i>
            </a>
            <a href="{{ route('dashboard', ['date' => $record->date?->format('Y-m-d')]) }}" class="btn btn-outline-secondary btn-sm" id="btn-back-dashboard">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Info Utama --}}
        <div class="col-12 col-lg-8">
            <div class="card chart-card">
                <div class="card-header py-3 fw-bold">
                    <i class="bi bi-clipboard-data me-2"></i>Informasi Produksi
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="text-muted small">Date</div>
                            <div class="fw-medium">{{ $record->date?->format('d-M-Y') ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted small">Mesin No.</div>
                            <div class="fw-bold fs-5 text-primary">{{ $record->machine_no }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted small">ID PDR</div>
                            <div class="fw-medium">{{ $record->id_pdr !== null && $record->id_pdr !== '' ? $record->id_pdr : '-' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="text-muted small">Operator</div>
                            <div class="fw-medium">{{ $record->operator ?? '-' }}</div>
                        </div>
                        
                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Part name</div>
                            <div class="fw-medium">{{ $record->part_name ?? '-' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Proses no. & name</div>
                            <div class="fw-medium">{{ $record->process ?? '-' }} - {{ $record->process_name ?? '-' }}</div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-6 col-md-3">
                            <div class="text-muted small">time start</div>
                            <div class="fw-medium">{{ $record->time_start ? \Carbon\Carbon::parse($record->time_start)->format('H:i') : '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">time finish</div>
                            <div class="fw-medium">{{ $record->time_finish ? \Carbon\Carbon::parse($record->time_finish)->format('H:i') : '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">time check qty</div>
                            <div class="fw-medium">{{ $record->time_input_qty_produksi ? \Carbon\Carbon::parse($record->time_input_qty_produksi)->format('H:i') : '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">work time eff</div>
                            @php
                                $wte = (float) ($record->work_time_eff_h ?? 0);
                                $wte_h = floor($wte);
                                $wte_m = round(($wte - $wte_h) * 60);
                            @endphp
                            <div class="fw-medium">{{ sprintf('%02d:%02d', $wte_h, $wte_m) }}</div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-6 col-md-4">
                            <div class="text-muted small">PPM</div>
                            <div class="fw-bold fs-5 text-warning">{{ number_format((float)($record->ppm ?? 0), 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel Produktivitas --}}
        <div class="col-12 col-lg-4">
            <div class="card chart-card mb-3">
                <div class="card-header py-3 fw-bold">
                    <i class="bi bi-speedometer me-2"></i>Produktivitas
                </div>
                <div class="card-body text-center">
                    @php
                        $productivity = (float) ($record->productivity ?? 0) * 100;
                        $target = (float) ($record->target_productivity ?? 0) * 100;
                        $isOnTarget = $record->is_on_target;
                        $progressColor = $isOnTarget ? '#198754' : '#dc3545';
                        $progressWidth = $target > 0 ? min(($productivity / $target) * 100, 100) : 0;
                    @endphp

                    <div class="mb-3">
                        <div class="display-4 fw-bold" style="color: {{ $progressColor }};">
                            {{ number_format($productivity, 0, ',', '.') }}%
                        </div>
                        <div class="text-muted">Produktivitas Aktual</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Produktivitas</span>
                            <span>{{ number_format($progressWidth, 0, ',', '.') }}% dari target</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px;">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $progressWidth }}%; background-color: {{ $progressColor }}; border-radius: 6px;"
                                aria-valuenow="{{ $progressWidth }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                    </div>

                    <div class="row text-center g-2">
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(13, 110, 253, 0.1);">
                                <div class="fw-bold text-primary fs-5">{{ number_format($record->qty_target ?? 0, 0, ',', '.') }}</div>
                                <div class="text-muted small">Qty Target</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: {{ $isOnTarget ? 'rgba(25, 135, 84, 0.1)' : 'rgba(220, 53, 69, 0.1)' }};">
                                <div class="fw-bold fs-5" style="color: {{ $progressColor }};">{{ number_format($record->qty_proses ?? 0, 0, ',', '.') }}</div>
                                <div class="text-muted small">Qty Actual</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 p-2 rounded text-center" style="background: {{ $isOnTarget ? 'rgba(25, 135, 84, 0.1)' : 'rgba(220, 53, 69, 0.1)' }};">
                        <div class="text-muted small">Target Produktivitas</div>
                        <div class="fw-bold fs-5" style="color: {{ $progressColor }};">
                            {{ number_format($target, 0, ',', '.') }}%
                        </div>
                        <span class="badge {{ $isOnTarget ? 'badge-on-target' : 'badge-below-target' }} mt-1">
                            <i class="bi {{ $isOnTarget ? 'bi-check-circle' : 'bi-exclamation-circle' }} me-1"></i>
                            {{ $isOnTarget ? 'On Target' : 'Below Target' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Catatan dari sumber (kolom Remark di Access / sync) — hanya baca --}}
        @if(!empty($record->remark))
        <div class="col-12">
            <div class="card chart-card mb-3">
                <div class="card-header py-3 fw-bold d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-journal-text me-2"></i>Catatan Produksi
                    </div>
                    <small class="text-muted fw-normal">dari sumber data</small>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded border" style="min-height: 100px;">
                        {!! nl2br(e($record->remark)) !!}
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
