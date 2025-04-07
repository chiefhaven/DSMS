@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
                {{Session::get('message')}}
            </div>
            @endif

            @role(['superAdmin', 'admin'])
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-sm-inline-block">Action</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2">
                        @role(['superAdmin', 'admin'])
                            <a href="{{ url('/addstudent') }}" class="dropdown-item nav-main-link">
                                <i class="fa fa-fw fa-user-plus mr-1"></i>&nbsp; Add student
                            </a>
                            <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                                <i class="fa fa-download"></i> &nbsp; Students report
                            </button>
                        @endcan
                        </div>
                    </div>
                </div>
            @endcan
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full" id="students">
        <div class="row">
            <!-- Active Students -->
            <div class="col-md-4 col-xl-4 col-sm-4">
            <div class="block block-rounded block-link-shadow border" @click="reloadTable('active')" style="cursor: pointer;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                <div>
                    <i class="fa fa-2x fa-check-circle text-success"></i>
                </div>
                <div class="ml-3 text-right">
                    <p class="font-size-h3 font-w300 mb-0">
                    {{ $students->where('status', '!=', 'Finished')->count() }}
                    </p>
                    <p class="mb-0">Active</p>
                </div>
                </div>
            </div>
            </div>

            <!-- Unassigned Students -->
            <div class="col-md-4 col-xl-4 col-sm-4">
            <div class="block block-rounded block-link-shadow border" @click="reloadTable('unassigned')" style="cursor: pointer;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                <div>
                    <i class="fa fa-2x fa-times-circle text-danger"></i>
                </div>
                <div class="mr-3 text-right">
                    <p class="font-size-h3 font-w900 mb-0">
                    {{ $students->where('fleet_id', null)->where('classroom_id', null)->where('status', '!=', 'Finished')->count() }}
                    </p>
                    <p class="mb-0">Unassigned</p>
                </div>
                </div>
            </div>
            </div>

            <!-- Finished Students -->
            <div class="col-md-4 col-xl-4 col-sm-4">
            <div class="block block-rounded block-link-shadow border" @click="reloadTable('finished')" style="cursor: pointer;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                <div>
                    <i class="fa fa-2x fa-check-circle text-primary"></i>
                </div>
                <div class="ml-3 text-right">
                    <p class="font-size-h3 font-w900 mb-0">
                    {{ $students->where('status', '==', 'Finished')->count() }}
                    </p>
                    <p class="mb-0">Finished/Cancelled</p>
                </div>
                </div>
            </div>
            </div>
        </div>

        <div class="block block-rounded block-bordered">
            <div class="content-full">
                <div class="row">
                    <div class="col-md-12 py-4">
                        <div class="m-4">
                            <table id="studentsTable" class="table table-bordered table-striped table-vcenter table-responsive">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="text-center" style="min-width: 100px;">Actions</th>
                                        <th style="min-width: 15rem;">Name</th>
                                        <th style="min-width: 15rem;">Course Enrolled</th>
                                        @role('superAdmin|admin')
                                            <th>Fees</th>
                                            <th>Balance</th>
                                        @endrole
                                        <th style="min-width: 10rem;">Registered on</th>
                                        @role('superAdmin|admin')
                                            <th style="min-width: 10rem;">Assigned</th>
                                        @endrole
                                        <th>Attendance</th>
                                        <th style="min-width: 10rem;">Course Status</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>TRN</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @role(['superAdmin', 'admin'])
        <!-- Payment Modal -->
            <div class="modal" id="modal-block-vcenter" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: none;" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="block block-rounded block-themed block-transparent mb-0">
                        <div class="block-header bg-primary-dark">
                            <h3 class="block-title">Filter to download report</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <form class="mb-5" action="{{ url('/studentsPdf') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6 mb-4">
                                        <label for="invoice_discount">Date</label>
                                        <select class="form-select dropdown-toggle" id="date" name="date">
                                                <option class="" value="all_time">All Time</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="balance">Balance</label>
                                        <select class="form-control dropdown-toggle" id="balance" name="balance">
                                                <option class="" value="all">All</option>
                                                <option class="text-left" value="balance">With balance</option>
                                                <option class="" value="no_balance">No balance</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="car">Assigned Car</label>
                                        <select class="form-select" id="fleet" name="fleet">
                                            <option class="" value="alltime" selected>All</option>
                                            @foreach($fleet as $fleet_option)
                                                <option value="{{$fleet_option->car_registration_number}}">{{$fleet_option->car_registration_number}} ({{$fleet_option->car_brand_model}})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="status">Student Status</label>
                                        <select class="form-control dropdown-toggle form-select" id="status" name="status">
                                                <option class="" value="allstatus" selected>All</option>
                                                <option value="inprogress">In progress</option>
                                                <option value="finished">Completed</option>
                                        </select>
                                    </div>
                                    <div class="block-content block-content-full text-end bg-body">
                                        <button type="submit" class="btn btn-primary">Download</button>
                                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    @endcan

    <script setup>
        const { createApp, ref, reactive, onMounted, nextTick } = Vue

        const students = createApp({
        setup() {

            const status = ref('active');

            onMounted(() => {
                nextTick(() => {
                  setTimeout(() => {
                    getStudents();
                  }, 100);
                });
            });

            const reloadTable = (val) => {
                status.value = val
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    $('#studentsTable').DataTable().ajax.reload();
                  }
            }

            const getStudents = () => {
                const table = $('#studentsTable').DataTable();
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    table.destroy();
                }
                $('#studentsTable').DataTable({
                  serverSide: true,
                  processing: true,
                  scrollCollapse: true,
                  scrollX: true,
                  ajax: {
                    url: '/api/students',
                    type: 'GET',
                    error: function (xhr, error, thrown) {
                      alert('An error occurred while fetching data. Please try again later.')
                    }
                  },

                  columns: [
                    { data: 'actions', className: 'text-center', orderable: false },
                    { data: 'full_name' },
                    { data: 'course_enrolled', className: 'text-wrap' },
                    { data: 'fees', className: 'text-right' },
                    { data: 'balance', className: 'text-right' },
                    { data: 'registered_on', className: 'text-center' },
                    { data: 'car_assigned', className: 'text-center' },
                    { data: 'attendance', className: 'text-center' },
                    { data: 'course_status', className: 'text-wrap' },
                    { data: 'phone' },
                    { data: 'email' },
                    { data: 'trn' }
                  ],
                  drawCallback: function () {
                    $('.delete-confirm').on('click', function (e) {
                      e.preventDefault();
                      var form = $(this).closest('form');
                      Swal.fire({
                        title: 'Delete Student',
                        text: 'Do you want to delete this student?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete!',
                        cancelButtonText: 'Cancel'
                      }).then((result) => {
                        if (result.isConfirmed) {
                          form.submit();
                          $('#studentsTable').DataTable().ajax.reload();
                        }
                      });
                    });
                  }
                });
            };

            return {
                reloadTable,
            }

        }})

        students.mount('#students');
    </script>

<!-- END Hero -->
@endsection