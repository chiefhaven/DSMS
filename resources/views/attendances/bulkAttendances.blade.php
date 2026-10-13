@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Bulk Attendance</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    @if(Session::has('message'))
                        <div class="alert alert-info mb-0">
                            {{ Session::get('message') }}
                        </div>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full" id="bulkAttendances">

        {{-- Bulk Attendance Table --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa fa-layer-group me-2"></i>
                    Bulk Attendance Records
                </h6>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2"
                        data-bs-toggle="tooltip"
                        title="Refresh table"
                        aria-label="Refresh table"
                        @click="reloadTable">
                    <i class="fa fa-sync-alt"></i>
                </button>
            </div>

            <div class="card-body p-0 position-relative">

                {{-- Skeleton Loader --}}
                <div id="bulkAttendanceSkeleton" class="p-3 d-none">
                    <div class="table-responsive">
                        <table class="table table-vcenter align-middle mb-0 fs-sm">
                            <thead class="bg-light">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    @for($i = 0; $i < 6; $i++)
                                        <th><div class="skeleton" style="height: 12px; width: 70%;"></div></th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @for($row = 0; $row < 8; $row++)
                                    <tr>
                                        @for($col = 0; $col < 6; $col++)
                                            <td>
                                                <div class="skeleton"
                                                     style="height: 14px; width: {{ rand(50, 95) }}%;"></div>
                                            </td>
                                        @endfor
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Actual Table --}}
                <div class="table-responsive p-3" id="bulkAttendanceTableWrapper">
                    <table id="bulkAttendanceTable"
                           class="table table-hover table-vcenter align-middle mb-0 fs-sm"
                           style="width: 100%;">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                <th class="text-center" style="min-width: 7rem;">Actions</th>
                                <th style="min-width: 20rem;">Students</th>
                                <th style="min-width: 14rem;">Entered by</th>
                                <th style="min-width: 2rem;">Description</th>
                                <th style="min-width: 12rem;">Created at</th>
                                <th style="min-width: 12rem;">Updated at</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const bulkAttendances = createApp({
            setup() {
                const loadingData = ref(false);

                const showSkeleton = () => {
                    $('#bulkAttendanceSkeleton').removeClass('d-none');
                    $('#bulkAttendanceTableWrapper').addClass('d-none');
                };

                const hideSkeleton = () => {
                    $('#bulkAttendanceSkeleton').addClass('d-none');
                    $('#bulkAttendanceTableWrapper').removeClass('d-none');
                };

                const showToast = (message, icon = 'success') => {
                    Swal.fire({
                        icon,
                        title: message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                };

                const deleteBulkAttendance = async (bulkAttendance) => {
                    const result = await Swal.fire({
                        title: 'Delete bulk attendance?',
                        text: 'Are you sure you want to delete this bulk attendance?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete',
                    });

                    if (!result.isConfirmed) return;

                    try {
                        NProgress.start();
                        await axios.delete(`/api/bulk-attendance/${bulkAttendance.id}`);
                        showToast('Bulk attendance deleted.');
                        $('#bulkAttendanceTable').DataTable().ajax.reload();
                    } catch (error) {
                        console.error(error);
                        showToast(error.response?.data?.message || 'An error occurred.', 'error');
                    } finally {
                        NProgress.done();
                        loadingData.value = false;
                    }
                };

                const getBulkAttendances = () => {
                    NProgress.start();
                    loadingData.value = true;

                    if ($.fn.DataTable.isDataTable('#bulkAttendanceTable')) {
                        $('#bulkAttendanceTable').DataTable().destroy();
                    }

                    showSkeleton();

                    $('#bulkAttendanceTable').DataTable({
                        serverSide: true,
                        processing: false,
                        scrollCollapse: true,
                        scrollX: true,
                        ajax: async function (data, callback) {
                            try {
                                const response = await axios.get('/api/bulk-attendances', {
                                    params: data
                                });
                                callback(response.data);
                            } catch (error) {
                                console.error('Error loading bulk attendances:', error);
                                callback({ data: [], recordsTotal: 0, recordsFiltered: 0 });
                            } finally {
                                loadingData.value = false;
                                NProgress.done();
                                hideSkeleton();
                            }
                        },
                        columns: [
                            { data: 'actions', className: 'text-center', orderable: false },
                            { data: 'students', className: 'text-wrap', orderable: false, searchable: false },
                            { data: 'entered_by', className: 'text-wrap', orderable: false, searchable: false },
                            { data: 'description', className: 'text-wrap' },
                            { data: 'created_at', className: 'text-wrap', orderable: false, searchable: false },
                            { data: 'updated_at', className: 'text-wrap', orderable: false, searchable: false }
                        ],
                        language: {
                            emptyTable: "No bulk attendance records found."
                        },
                        drawCallback: function () {
                            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                                bootstrap.Tooltip.getOrCreateInstance(el);
                            });
                        }
                    });
                };

                const reloadTable = () => {
                    showSkeleton();
                    if ($.fn.DataTable.isDataTable('#bulkAttendanceTable')) {
                        $('#bulkAttendanceTable').DataTable().ajax.reload(() => hideSkeleton());
                    }
                };

                onMounted(() => getBulkAttendances());

                return {
                    loadingData,
                    deleteBulkAttendance,
                    reloadTable
                };
            },
        });

        window.bulkAttendanceApp = bulkAttendances.mount('#bulkAttendances');

        window.openDeleteBulkAttendance = bulkAttendance => {
            window.bulkAttendanceApp.deleteBulkAttendance(bulkAttendance);
        };
    </script>

    <style>
        /* Skeleton shimmer */
        .skeleton {
            background: linear-gradient(90deg, #e9edf3 25%, #f4f7fb 50%, #e9edf3 75%);
            background-size: 200% 100%;
            border-radius: 0.375rem;
            animation: skeleton-shimmer 1.4s ease-in-out infinite;
            display: inline-block;
        }
        @keyframes skeleton-shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Fade-in the real table when it appears */
        #bulkAttendanceTableWrapper {
            animation: fadeIn 0.25s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .tracking-wide { letter-spacing: 0.05em; }
        .fs-xs { font-size: 0.75rem; }
        .fs-sm { font-size: 0.875rem; }

        /* Hover accent for the new card style */
        #bulkAttendanceTable.table-hover > tbody > tr:hover > * {
            --bs-table-accent-bg: rgba(13, 110, 253, 0.04);
        }
    </style>
@endsection
