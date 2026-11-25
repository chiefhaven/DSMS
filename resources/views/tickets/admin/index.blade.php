@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <h1 class="fs-3 fw-semibold my-2 my-sm-3">
                <i class="fa fa-headset text-primary me-2" aria-hidden="true"></i>Student support tickets
            </h1>
            <p class="fs-sm text-muted mb-0">Review, assign, and reply to student requests.</p>
        </div>
    </div>

    <div class="content content-full">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <div class="block block-rounded block-bordered mb-4">
            <div class="block-header block-header-default">
                <h2 class="block-title"><i class="fa fa-filter text-primary me-2" aria-hidden="true"></i>Filter tickets</h2>
            </div>
            <div class="block-content block-content-full">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <label class="form-label mb-1" for="ticket-status-filter">Status</label>
                        <select class="form-select" id="ticket-status-filter">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <label class="form-label mb-1" for="ticket-date-from">Created from</label>
                        <input class="form-control js-datepicker" id="ticket-date-from" type="text" placeholder="YYYY-MM-DD" value="{{ $selectedDateFrom }}" autocomplete="off">
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <label class="form-label mb-1" for="ticket-date-to">Created to</label>
                        <input class="form-control js-datepicker" id="ticket-date-to" type="text" placeholder="YYYY-MM-DD" value="{{ $selectedDateTo }}" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        @if ($canViewTicketSummaries)
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
                @foreach ([
                    ['key' => 'total', 'label' => 'Total tickets', 'value' => $ticketSummary['total'], 'icon' => 'fa-ticket-alt', 'color' => 'primary'],
                    ['key' => 'open', 'label' => 'Open', 'value' => $ticketSummary['open'], 'icon' => 'fa-envelope-open', 'color' => 'info'],
                    ['key' => 'in_progress', 'label' => 'In progress', 'value' => $ticketSummary['in_progress'], 'icon' => 'fa-spinner', 'color' => 'warning'],
                    ['key' => 'waiting_on_student', 'label' => 'Waiting on student', 'value' => $ticketSummary['waiting_on_student'], 'icon' => 'fa-clock', 'color' => 'secondary'],
                    ['key' => 'completed', 'label' => 'Resolved / closed', 'value' => $ticketSummary['completed'], 'icon' => 'fa-check-circle', 'color' => 'success'],
                    ['key' => 'resolve_rate', 'label' => 'Resolve rate', 'value' => $ticketSummary['resolve_rate'], 'icon' => 'fa-percent', 'color' => 'primary'],
                ] as $summary)
                    <div class="col">
                        <div class="block block-rounded h-100 mb-0">
                            <div class="block-content block-content-full d-flex align-items-center gap-3">
                                <span class="item item-rounded bg-{{ $summary['color'] }}-light text-{{ $summary['color'] }}">
                                    <i class="fa {{ $summary['icon'] }} fs-4" aria-hidden="true"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <div class="fs-3 fw-semibold" data-ticket-summary="{{ $summary['key'] }}">{{ number_format($summary['value']) }}{{ $summary['key'] === 'resolve_rate' ? '%' : '' }}</div>
                                    <div class="text-muted fs-xs text-truncate">{{ $summary['label'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h2 class="block-title" id="student-tickets-table-title">
                    <i class="fa fa-list text-primary me-2" aria-hidden="true"></i>All tickets
                </h2>
            </div>
            <div class="block-content block-content-full fs-sm">
                <div id="ticket-table-error" class="alert alert-danger d-none" role="alert"></div>
                <table id="student-tickets-table" class="table table-hover table-vcenter align-middle mb-0 fs-sm w-100" aria-labelledby="student-tickets-table-title">
                    <thead class="bg-body-light">
                        <tr>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Ticket</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Student</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Subject</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Category</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Status</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Assigned to</th>
                            <th scope="col" class="fs-xs text-uppercase text-muted">Updated</th>
                            <th scope="col" class="text-end fs-xs text-uppercase text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            if ($.fn.datepicker) {
                $('.js-datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
                    todayHighlight: true
                });
            }

            const table = $('#student-tickets-table').DataTable({
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                scrollX: true,
                ajax: {
                    url: @json(route('admin.tickets.index')),
                    data: function (data) {
                        data.status = $('#ticket-status-filter').val();
                        data.date_from = $('#ticket-date-from').val();
                        data.date_to = $('#ticket-date-to').val();
                    },
                    error: function (xhr) {
                        $('#student-tickets-table_processing').hide();
                        $('#ticket-table-error').removeClass('d-none')
                            .text(xhr.status === 401 || xhr.status === 403
                                ? 'Session expired or access denied. Reload the page.'
                                : 'Unable to load tickets. Reload the page to try again.');
                    },
                    dataSrc: function (response) {
                        $('#ticket-table-error').addClass('d-none').text('');
                        Object.entries(response.ticketSummary || {}).forEach(([key, value]) => {
                            const summary = document.querySelector(`[data-ticket-summary="${key}"]`);
                            if (summary) {
                                const formattedValue = Number(value).toLocaleString('en-US');
                                summary.textContent = key === 'resolve_rate' ? `${formattedValue}%` : formattedValue;
                            }
                        });
                        return response.data;
                    }
                },
                columns: [
                    { data: 'ticket', name: 'id', orderable: true, searchable: false, className: 'text-nowrap' },
                    { data: 'student', orderable: false, searchable: false },
                    { data: 'subject', name: 'subject', className: 'fw-semibold' },
                    { data: 'category', name: 'category', searchable: false },
                    { data: 'status', name: 'status', searchable: false },
                    { data: 'assigned_to', orderable: false, searchable: false },
                    { data: 'updated_at', name: 'updated_at', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' }
                ],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[6, 'desc']],
                pagingType: 'simple_numbers',
                language: {
                    emptyTable: 'No tickets found.',
                    zeroRecords: 'No matching tickets.'
                }
            });

            function reloadTickets() {
                const dateFrom = $('#ticket-date-from').val();
                const dateTo = $('#ticket-date-to').val();

                if (dateFrom && dateTo && dateTo < dateFrom) {
                    $('#ticket-table-error').removeClass('d-none')
                        .text('Created to must be on or after Created from.');
                    return;
                }

                $('#ticket-table-error').addClass('d-none').text('');
                table.ajax.reload();
            }

            $('#ticket-status-filter, #ticket-date-from, #ticket-date-to').on('change', reloadTickets);
        });
    </script>
@endpush
