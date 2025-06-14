<div class="row items-push">
    <!-- Quotes Partial -->
    <div class="col-md-12">
        @include('dashboard_partials.quotes')
    </div>

    <!-- Scan to Pay for Finance Admin only -->
    @if (Auth::user()->hasRole('financeAdmin'))
        <div class="col-xl-12">
            @include('/dashboard_partials/scan_to_pay')
        </div>
    @endif

    <!-- Students Block -->
    <div class="col-xl-6">
        <div class="block block-rounded block-bordered block-mode-loading-refresh h-100 mb-0">
            <div class="block-header border-bottom">
                <h3 class="block-title">Students</h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="fa fa-sync"></i>
                    </button>
                    <button type="button" class="btn-block-option">
                        <i class="fa fa-wrench"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                        <thead>
                            <tr class="text-uppercase">
                                <th class="fw-bold text-center" style="width: 120px;">Photo</th>
                                <th class="fw-bold">Name</th>
                                <th class="d-none d-sm-table-cell fw-bold text-center">Course</th>
                                <th class="fw-bold text-center" style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($student as $student)
                                <tr>
                                    <td class="text-center">
                                        <img class="img-avatar img-avatar32 img-avatar-thumb" src="{{ asset('media/avatars/avatar2.jpg') }}" alt="Avatar">
                                    </td>
                                    <td>
                                        <div class="fw-semibold fs-base">{{ $student->fname }} {{ $student->mname }} {{ $student->sname }}</div>
                                        <div class="text-muted">
                                            {{ $student->user->email ?? '' }}
                                        </div>
                                    </td>
                                    <td class="d-none d-sm-table-cell fs-base text-center">
                                        @if (isset($student->course->name))
                                            <span class="badge bg-dark">
                                                {{ $student->course->name }}
                                                <div class="text-muted">{{ $student->course->duration }} days</div>
                                            </span>
                                        @else
                                            <a href="#">
                                                <span class="badge bg-danger">Not enrolled yet</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('/viewstudent', $student->id) }}" data-bs-toggle="tooltip" title="View Student">
                                            <i class="fa fa-fw fa-user-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Block -->
    <div class="col-xl-6">
        <div class="block block-rounded block-bordered block-mode-loading-refresh h-100 mb-0">
            <div class="block-header border-bottom">
                <h3 class="block-title">Invoices</h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="fa fa-sync"></i>
                    </button>
                    <button type="button" class="btn-block-option">
                        <i class="fa fa-wrench"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                        <thead>
                            <tr class="text-uppercase">
                                <th class="fw-bold">Invoice No</th>
                                <th class="d-none d-sm-table-cell fw-bold">Date</th>
                                <th class="fw-bold">Student</th>
                                <th class="fw-bold">Balance</th>
                                <th class="fw-bold text-center" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">
                                            <a href="{{ url('/view-invoice', $invoice->id) }}">{{ $invoice->invoice_number }}</a>
                                        </span>
                                    </td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="fs-sm text-muted">{{ $invoice->date_created->format('j F, Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-warning">{{ $invoice->student->fname }} {{ $invoice->student->mname }} {{ $invoice->student->sname }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-warning">K{{ number_format($invoice->invoice_balance, 2) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('/view-invoice', $invoice->id) }}" data-bs-toggle="tooltip" title="Manage">
                                            <i class="fa fa-fw fa-life-ring me-2"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
