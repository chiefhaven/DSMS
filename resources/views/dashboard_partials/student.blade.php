@php
    $invoice = $student?->Invoice;
@endphp

<div class="row g-3" id="studentDashboardSummary">
    <div class="col-12">
        @include('dashboard_partials.quotes')
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                         style="width: 48px; height: 48px;">
                        <i class="fa fa-car text-primary" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-end overflow-hidden">
                    <div class="fw-bold fs-5 text-dark text-truncate lh-1 mb-1">
                        {{ $student?->Course?->name ?? 'Not assigned' }}
                    </div>
                    <div class="fs-xs text-muted text-truncate text-uppercase tracking-wide">Current course</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                         style="width: 48px; height: 48px;">
                        <i class="fa fa-calendar-check text-success" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-end overflow-hidden">
                    <div class="fw-bold fs-5 text-dark lh-1 mb-1">{{ number_format($studentAttendanceCount) }}</div>
                    <div class="fs-xs text-muted text-truncate text-uppercase tracking-wide">Attendance records</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                         style="width: 48px; height: 48px;">
                        <i class="fa fa-receipt text-warning" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-end overflow-hidden">
                    <div class="fw-bold fs-5 text-dark text-truncate lh-1 mb-1">
                        {{ $invoice ? 'K' . number_format((float) $invoice->invoice_balance, 2) : 'No invoice' }}
                    </div>
                    <div class="fs-xs text-muted text-truncate text-uppercase tracking-wide">Fees balance</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center"
                         style="width: 48px; height: 48px;">
                        <i class="fa fa-comments text-info" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-end overflow-hidden">
                    <div class="fw-bold fs-5 text-dark lh-1 mb-1">{{ number_format($openStudentTicketCount) }}</div>
                    <div class="fs-xs text-muted text-truncate text-uppercase tracking-wide">Open support tickets</div>
                    @can('view student tickets')
                        <a class="fs-xs d-inline-block mt-2" href="{{ route('student.tickets.index') }}">
                            {{ number_format($studentTicketCount) }} total {{ \Illuminate\Support\Str::plural('ticket', $studentTicketCount) }} - View tickets
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
