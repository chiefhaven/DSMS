@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Administrators</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <a href="{{ route('administrators.create') }}" class="btn btn-primary">
                        <i class="fa fa-fw fa-user-plus me-1"></i> Add Administrator
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if(Session::has('message'))
            <div class="alert alert-success">{{ Session::get('message') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <button type="button" id="administrators-table-refresh"
                        class="btn btn-sm btn-outline-secondary rounded-2"
                        data-bs-toggle="tooltip" title="Refresh table" aria-label="Refresh table">
                    <i class="fa fa-sync-alt"></i>
                </button>
            </div>
            <div class="card-body p-0 position-relative">
                <div id="administrators-table-error" class="alert alert-danger m-3 d-none" role="alert"></div>
                <div id="administrators-skeleton" class="table-responsive p-3" aria-hidden="true">
                    <table class="table table-vcenter align-middle mb-0 fs-sm w-100">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                @for ($column = 0; $column < 5; $column++)
                                    <th><span class="administrator-skeleton" style="width: 70%;"></span></th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @for ($row = 0; $row < 8; $row++)
                                <tr>
                                    @for ($column = 0; $column < 5; $column++)
                                        <td><span class="administrator-skeleton" style="width: {{ 45 + (($row * 17 + $column * 13) % 46) }}%;"></span></td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div id="administrators-table-wrapper" class="table-responsive p-3 d-none">
                    <table id="administrators-table" class="table table-hover table-vcenter align-middle mb-0 fs-sm w-100">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                <th class="text-center" style="min-width: 100px;">Actions</th>
                                <th style="min-width: 15rem;">Name</th>
                                <th>Roles</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        #administrators-table-wrapper .tracking-wide,
        #administrators-skeleton .tracking-wide {
            letter-spacing: .05em;
        }
        #administrators-table-wrapper .fs-xs,
        #administrators-skeleton .fs-xs {
            font-size: .75rem;
        }
        #administrators-table-wrapper .fs-sm,
        #administrators-skeleton .fs-sm {
            font-size: .875rem;
        }
        .administrator-skeleton {
            display: inline-block;
            height: 14px;
            border-radius: .375rem;
            background: linear-gradient(90deg, #e9edf3 25%, #f4f7fb 50%, #e9edf3 75%);
            background-size: 200% 100%;
            animation: administrator-skeleton-shimmer 1.4s ease-in-out infinite;
        }
        @keyframes administrator-skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    <script>
        $(function () {
            const table = $('#administrators-table').DataTable({
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                scrollX: true,
                ajax: {
                    url: @json(route('administrators.index')),
                    error: function (xhr) {
                        $('#administrators-table_processing').hide();
                        $('#administrators-skeleton').addClass('d-none');
                        $('#administrators-table-wrapper').removeClass('d-none');
                        $('#administrators-table-error').removeClass('d-none')
                            .text(xhr.status === 401 || xhr.status === 403
                                ? 'Session expired or access denied. Reload the page.'
                                : 'Unable to load administrators. Reload the page to try again.');
                    },
                    dataSrc: function (response) {
                        $('#administrators-skeleton').addClass('d-none');
                        $('#administrators-table-wrapper').removeClass('d-none');
                        $('#administrators-table-error').addClass('d-none').text('');
                        return response.data;
                    }
                },
                columns: [
                    { data: 'actions', className: 'text-center', orderable: false, searchable: false },
                    { data: 'administrator', name: 'fname', className: 'text-nowrap' },
                    { data: 'roles', orderable: false, searchable: false },
                    { data: 'email', name: 'User.email' },
                    { data: 'phone', name: 'phone', defaultContent: 'N/A' }
                ],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'asc']],
                pagingType: 'simple_numbers',
                language: {
                    emptyTable: 'No administrators found.',
                    zeroRecords: 'No matching administrators.'
                },
                initComplete: function () {
                    this.api().columns.adjust();
                }
            });

            $('#administrators-table-refresh').on('click', function () {
                table.ajax.reload(null, false);
            });

            $('#administrators-table').on('click', '.delete-confirm', function (event) {
                event.preventDefault();
                const form = $(this).closest('form');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete administrator?',
                    text: `Are you sure you want to delete ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.trigger('submit');
                    }
                });
            });
        });
    </script>
@endsection
