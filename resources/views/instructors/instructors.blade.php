@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Instructors</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="{{ route('instructors.create') }}" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add Instructor
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
          @if(Session::has('message'))
            <div class="alert alert-success">
              {{Session::get('message')}}
            </div>
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
              <div class="d-flex gap-1">
                  <button type="button" id="instructors-table-refresh"
                          class="btn btn-sm btn-outline-secondary rounded-2"
                          data-bs-toggle="tooltip" title="Refresh table" aria-label="Refresh table">
                      <i class="fa fa-sync-alt"></i>
                  </button>
              </div>
          </div>
          <div class="card-body p-0 position-relative">
              <div id="instructors-table-error" class="alert alert-danger m-3 d-none" role="alert"></div>
              <div id="instructors-skeleton" class="table-responsive p-3" aria-hidden="true">
                  <table class="table table-vcenter align-middle mb-0 fs-sm w-100">
                      <thead class="bg-light">
                          <tr class="text-uppercase fs-xs text-muted tracking-wide">
                              @for ($column = 0; $column < 7; $column++)
                                  <th><span class="instructor-skeleton" style="width: 70%;"></span></th>
                              @endfor
                          </tr>
                      </thead>
                      <tbody>
                          @for ($row = 0; $row < 8; $row++)
                              <tr>
                                  @for ($column = 0; $column < 7; $column++)
                                      <td><span class="instructor-skeleton" style="width: {{ 45 + (($row * 17 + $column * 13) % 46) }}%;"></span></td>
                                  @endfor
                              </tr>
                          @endfor
                      </tbody>
                  </table>
              </div>
              <div id="instructors-table-wrapper" class="table-responsive p-3 d-none">
                  <table id="instructors-table" class="table table-hover table-vcenter align-middle mb-0 fs-sm w-100">
                      <thead class="bg-light">
                          <tr class="text-uppercase fs-xs text-muted tracking-wide">
                              <th class="text-center" style="min-width: 100px;">Actions</th>
                              <th style="min-width: 15rem;">Instructor</th>
                              <th>Department</th>
                              <th style="min-width: 10rem;">Assignments</th>
                              <th>Email</th>
                              <th>Phone</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody></tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
  <!-- END Hero -->
  <style>
    #instructors-table-wrapper {
        animation: instructor-table-fade-in .25s ease-in;
    }

    #instructors-table-wrapper .tracking-wide,
    #instructors-skeleton .tracking-wide {
        letter-spacing: .05em;
    }

    #instructors-table-wrapper .fs-xs,
    #instructors-skeleton .fs-xs {
        font-size: .75rem;
    }

    #instructors-table-wrapper .fs-sm,
    #instructors-skeleton .fs-sm {
        font-size: .875rem;
    }

    @keyframes instructor-table-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .instructor-skeleton {
        display: inline-block;
        height: 14px;
        border-radius: .375rem;
        background: linear-gradient(90deg, #e9edf3 25%, #f4f7fb 50%, #e9edf3 75%);
        background-size: 200% 100%;
        animation: instructor-skeleton-shimmer 1.4s ease-in-out infinite;
    }

    @keyframes instructor-skeleton-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
  </style>
  <script>
    $(function () {
        const table = $('#instructors-table').DataTable({
            processing: true,
            serverSide: true,
            scrollCollapse: true,
            scrollX: true,
            ajax: {
                url: @json(route('instructors.index')),
                error: function (xhr) {
                    $('#instructors-table_processing').hide();
                    $('#instructors-skeleton').addClass('d-none');
                    $('#instructors-table-wrapper').removeClass('d-none');
                    $('#instructors-table-error')
                        .removeClass('d-none')
                        .text(xhr.status === 401 || xhr.status === 403
                            ? 'Session expired or access denied. Reload the page.'
                            : 'Unable to load instructors. Reload the page to try again.');
                },
                dataSrc: function (response) {
                    $('#instructors-skeleton').addClass('d-none');
                    $('#instructors-table-wrapper').removeClass('d-none');
                    $('#instructors-table-error').addClass('d-none').text('');
                    return response.data;
                }
            },
            columns: [
                { data: 'actions', name: 'actions', className: 'text-center', orderable: false, searchable: false },
                { data: 'instructor', name: 'fname', className: 'text-nowrap', orderable: false },
                { data: 'department_name', name: 'department_name', orderable: false, searchable: false },
                { data: 'assignment', name: 'assignment', orderable: false, searchable: false },
                { data: 'email', name: 'email', orderable: false },
                { data: 'phone', name: 'phone' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false }
            ],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']],
            pagingType: 'simple_numbers',
            language: {
                emptyTable: 'No instructors found.',
                zeroRecords: 'No matching instructors.'
            },
            initComplete: function () {
                this.api().columns.adjust();
            }
        });

        $('#instructors-table-refresh').on('click', function () {
            table.ajax.reload(null, false);
        });

        $('#instructors-table').on('click', '.delete-confirm', function (event) {
            event.preventDefault();
            const form = $(this).closest('form');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Delete instructor?',
                text: `Delete ${name}? All lessons belonging to this instructor will be transferred to the Super instructor.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                dangerMode: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.trigger('submit');
                }
            });
        });
    });
  </script>

@endsection
