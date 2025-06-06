@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class=""  id="student">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$student->fname}} {{$student->mname}} {{$student->sname}}</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">

                    <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-sm-inline-block">Action</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2">
                        @role(['superAdmin'])
                            <form method="GET" action="/edit-student/{{$student->id}}">
                                {{ csrf_field() }}
                                <button class="dropdown-item nav-main-link" type="submit">
                                    <i class="nav-main-link-icon  fas fa-pencil"></i>Edit profile
                                </button>
                            </form>
                            <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                                <i class="nav-main-link-icon  fas fa-file-invoice"></i>Add payment
                            </button>
                        @endrole
                        <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#change-status">
                            <i class="nav-main-link-icon  fas fa-toggle-on"></i>Change status
                        </button>
                        </div>
                    </div>
                    </div>
                </ol>
            </nav>
            </div>
        </div>
    </div>
    <div class="content content-full">
        @if (Session::has('message'))
            <div class="alert alert-{{ Session::get('alert-type', 'info') }}">
                {{ Session::get('message') }}
            </div>
        @endif
        <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-block" role="tablist">
            <li class="nav-item">
            <button class="nav-link active" id="student-details-tab" data-bs-toggle="tab" data-bs-target="#student-details" role="tab" aria-controls="student-details" aria-selected="true">
                Student Details
            </button>
            </li>
            @role(['superAdmin', 'admin'])
                <li class="nav-item">
                <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" role="tab" aria-controls="invoices" aria-selected="false">
                    Invoices
                </button>
                </li>
                <li class="nav-item">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" role="tab" aria-controls="payments" aria-selected="false">
                    Payments
                </button>
                </li>
            @endcan
        </ul>
        <div class="block-content tab-content">
            <div class="tab-pane fade active show" id="student-details" role="tabpanel" aria-labelledby="student-details-tab">
                <div class="content-full">
                    <div class="row">
                        <div class="col-md-6 py-4">
                            <div class="card p-4 h-100">
                                <!-- Profile Header -->
                                <div class="text-center mb-4">
                                    <img class="img-avatar img-avatar96 img-avatar-thumb mb-3"
                                         src="{{ $student->avatar_url ?? asset('media/avatars/avatar2.jpg') }}"
                                         alt="{{ $student->fname }}'s avatar">
                                    <h2 class="mb-1">
                                        {{ $student->fname }} {{ $student->mname }} {{ $student->sname }}
                                    </h2>
                                    <span class="badge bg-primary">{{ $student->student_id }}</span>
                                </div>

                                <!-- Personal Details -->
                                <div class="mb-4">
                                    <h5 class="border-bottom pb-2 mb-3">Personal Details</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-venus-mars me-2 text-muted"></i>
                                                <span>{{ $student->gender }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-phone me-2 text-muted"></i>
                                                <span>{{ $student->phone ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="d-flex">
                                                <i class="fas fa-envelope me-2 text-muted mt-1"></i>
                                                <span>{{ $student->user->email ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="d-flex">
                                                <i class="fas fa-map-marker-alt me-2 text-muted mt-1"></i>
                                                <span>{{ $student->address ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <i class="fas fa-id-card me-2 text-muted mt-1"></i>
                                                <span>TRN: {{ $student->trn ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Car Assignment Section (Admin Only) -->
                                @role(['superAdmin','admin'])
                                <div class="mt-auto">
                                    <h5 class="border-bottom pb-2 mb-3">Vehicle Assignment</h5>
                                    @if(isset($student->fleet->car_brand_model))
                                        <div class="alert alert-warning py-2">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-car me-3"></i>
                                                        <div>
                                                            <strong>{{ $student->fleet->car_registration_number }}</strong>
                                                            <div class="small">{{ $student->fleet->car_brand_model }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <button type="button"
                                                            @click="getFleet()"
                                                            class="btn btn-sm btn-outline-warning me-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target=".assignCar">
                                                        <i class="fas fa-sync-alt me-1"></i> Reassign
                                                    </button>
                                                    <button type="button"
                                                            @click="unAssignCar()"
                                                            class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times me-1"></i> Unassign
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-danger py-2">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <i class="fas fa-exclamation-circle me-2"></i>
                                                    <strong>No vehicle assigned</strong>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <button type="button"
                                                            @click="getFleet()"
                                                            class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target=".assignCar">
                                                        <i class="fas fa-plus me-1"></i> Assign Vehicle
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @endrole
                            </div>
                        </div>
                        <div class="col-md-6 py-4">
                            <div class="card p-4 h-100">
                                <!-- General Information Section -->
                                <h5 class="card-title border-bottom pb-2 mb-4">General Information</h5>

                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            @role(['superAdmin', 'admin'])
                                            <tr>
                                                <th width="40%" class="text-muted">Enrollment Date</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        {{ $student->invoice->created_at->format('j F, Y') }}
                                                    @else
                                                        <a href="{{ url('/addinvoice', $student->id) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-plus-circle me-1"></i> Enroll Course
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endrole

                                            <tr>
                                                <th class="text-muted">Course</th>
                                                <td>
                                                    @if(isset($student->invoice) && isset($student->course))
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <strong>{{ $student->course->name }}</strong>
                                                                <div class="small text-muted">{{ $student->course->duration }} days program</div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Not enrolled</span>
                                                    @endif
                                                </td>
                                            </tr>

                                            @role(['superAdmin', 'admin'])
                                            <tr>
                                                <th class="text-muted">Classroom</th>
                                                <td>
                                                    @if(isset($student->classroom))
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>{{ $student->classroom->name }}</strong>
                                                                <div class="small text-muted">{{ $student->classroom->location }}</div>
                                                            </div>
                                                            <button type="button"
                                                                    @click="getClassRooms()"
                                                                    class="btn btn-sm btn-outline-warning"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target=".assignClassRoom">
                                                                <i class="fas fa-sync-alt me-1"></i> Reassign
                                                            </button>
                                                        </div>
                                                    @else
                                                        <button type="button"
                                                                @click="getClassRooms()"
                                                                class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target=".assignClassRoom">
                                                            <i class="fas fa-plus me-1"></i> Assign Classroom
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>

                                            <!-- Payment Information -->
                                            <tr class="border-top">
                                                <th class="text-muted">Course Fees</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        <span class="fw-bold">K{{ number_format($student->invoice->invoice_total) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Amount Paid</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        <span class="text-success fw-bold">K{{ number_format($student->invoice->invoice_amount_paid) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Balance</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        <span class="{{ $student->invoice->invoice_balance > 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                                            K{{ number_format($student->invoice->invoice_balance) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endrole
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Attendance Progress -->
                                <div class="mt-4">
                                    <h5 class="border-bottom pb-2 mb-3">Attendance Progress</h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Completion: {{ $attendancePercent }}%</span>
                                        <span class="text-muted small">{{ $attendanceTheoryCount }} theory / {{ $attendancePracticalCount }} practical</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                             role="progressbar"
                                             style="width: {{ $attendancePercent }}%"
                                             aria-valuenow="{{ $attendancePercent }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <!-- Course Status -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Course Status</h5>
                                        <span class="badge bg-{{ $student->status === 'Active' ? 'success' : ($student->status === 'Completed' ? 'primary' : 'warning') }}">
                                            {{ $student->status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 py-4">
                            <div class="card p-4">
                                <h3>Downloads</h3>
                                <table class="table table-responsive table-striped">
                                    <thead>
                                        <th>#</th>
                                        <th style="width:90%">Description</th>
                                        <th>Action</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                        <td>1</td>
                                        <td>Traffic Register Card-Reference</td>
                                        <td>
                                            <form method="POST" action="{{ url('/trafic-card-reference-letter', $student->id) }}">
                                            {{ csrf_field() }}
                                            <button class="btn btn-primary" type="submit">Download</button>
                                            </form>
                                        </td>
                                        </tr>
                                        <tr>
                                        <td>2</td>
                                        <td>Highway code 1 Aptitude Reference Letter</td>
                                        <td>
                                            <form method="POST" action="{{ url('/aptitude-test-reference-letter', $student->id) }}">
                                            {{ csrf_field() }}
                                            <button class="btn btn-primary" type="submit">Download</button>
                                            </form>
                                        </td>
                                        </tr>
                                        <tr>
                                        <td>3</td>
                                        <td>Highway code 2 Aptitude Reference Letter</td>
                                        <td>
                                            <form method="POST" action="{{ url('/second-aptitude-test-reference-letter', $student->id) }}">
                                            {{ csrf_field() }}
                                            <button class="btn btn-primary" type="submit">Download</button>
                                            </form>
                                        </td>
                                        </tr>
                                        <tr>
                                        <td>4</td>
                                        <td>Final Reference Letter</td>
                                        <td>
                                            <form method="POST" action="{{ url('/final-test-reference-letter', $student->id) }}">
                                            {{ csrf_field() }}
                                            <button class="btn btn-primary" type="submit">Download</button>
                                            </form>
                                        </td>
                                        </tr>
                                        <tr>
                                        <td>5</td>
                                        <td>Lesson Attendance Report</td>
                                        <td>
                                            <form method="POST" action="{{ url('/lesson-report', $student->id) }}">
                                            {{ csrf_field() }}
                                            <button class="btn btn-primary" type="submit">Download</button>
                                            </form>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @include('students.partials.invoicesPayments')
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
                    <h3 class="block-title">Add Payment</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <form class="mb-5" action="{{ url('/add-payment') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                        @csrf

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($errors->any())
                            <script>
                                Swal.fire({
                                    icon: '{{ Session::get('alert-type', 'error') }}',
                                    title: 'Payment not entered',
                                    html: `
                                        <ul>
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    `,
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            </script>
                        @endif

                        @if(isset($student->invoice->created_at))
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{$student->invoice->invoice_number}}" hidden>
                        @else
                        @endif

                        <!-- Date Created Field -->
                        <div class="col-md-12 form-floating mb-4">
                            <input type="date" class="form-control @error('date_created') is-invalid @enderror" id="date_created" name="date_created" placeholder="Enter invoice date" value="{{ old('date_created') }}">
                            <label for="invoice_discount">Date</label>
                            @error('date_created')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Paid Amount Field -->
                            <div class="col-6 form-floating mb-4">
                                <input type="number" class="form-control @error('paid_amount') is-invalid @enderror" id="paid_amount" name="paid_amount" value="{{ old('paid_amount', 0) }}">
                                <label for="invoice_discount">Amount</label>
                                @error('paid_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Payment Method Field -->
                            <div class="col-6 form-floating mb-4">
                                <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                    @foreach (App\Models\PaymentMethod::all() as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" {{ old('payment_method') == $paymentMethod->id ? 'selected' : '' }}>
                                            {{ $paymentMethod->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="district">Payment Method</label>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Proof Field -->
                        <div class="col-12 form-floating mb-4">
                            <input type="file" class="form-control @error('payment_proof') is-invalid @enderror" id="payment_proof" name="payment_proof" placeholder="Upload a receipt">
                            <label for="invoice_discount">Payment proof</label>
                            @error('payment_proof')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit and Close Buttons -->
                        <div class="block-content block-content-full text-end bg-body">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
            </div>
        </div>
    @endcan

    @include('students.partials.assignCarModal')
    @include('students.partials.assignClassRoomModal')
</div>

@include('students.partials.changeStatus')

<script setup>
    const { createApp, ref, reactive } = Vue

    const app = createApp({
      setup() {

        const cars = ref([])
        const fleetRegNumber = ref(null)
        const fleet = ref('')
        const classRoom = ref('{{ $student->classroom->id ?? null }}');
        const classRooms = ref([]);

        function getFleet(){
            NProgress.start();

            axios.get('/getFleet').then(response => {
                if(response.status==200){
                    cars.value = response.data
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });

            NProgress.done();

        }

        const getClassRooms = () => {
            NProgress.start();

            axios.get('/getClassRooms')
                .then(response => {
                    if (response.status === 200 && response.data) {
                        classRooms.value = response.data;
                    } else {
                        notification('Unexpected response from server', 'error');
                    }
                })
                .catch(error => {
                    if (error.response && error.response.data && error.response.data.errors) {
                        notification(error.response.data.errors.message, 'error');
                    } else {
                        notification('An error occurred while fetching classrooms', 'error');
                    }
                })
                .finally(() => {
                    NProgress.done();
                });
        };

        function assign(){

            NProgress.start();

            axios.post('/assignCar', { student: '{{ $student->id }}', fleet: fleetRegNumber.value  }).then(response => {
                if(response.status==200){
                    notification(response.data,'success')
                    location.reload();
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });

            NProgress.done();
        }

        function unAssignCar(){

            NProgress.start();

            axios.post('/unAssignCar', { student: '{{ $student->id }}', fleet: fleetRegNumber.value  }).then(response => {
                if(response.status==200){
                    notification(response.data,'success')
                    location.reload();
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });

            NProgress.done();
        }

        const assignClassRoom = () => {
            NProgress.start();

            axios.post('/assign-class-room', {
                student: '{{ $student->id }}',
                classroom: classRoom.value
            })
            .then(response => {
                if (response.status === 200) {
                    notification(response.data, 'success');
                    location.reload();
                } else {
                    notification('Unexpected response from server', 'error');
                }
            })
            .catch(error => {
                if (error.response && error.response.data && error.response.data.errors) {
                    notification(error.response.data.errors.message, 'error');
                } else {
                    console.log(error);
                    notification('An error occurred while assigning the classroom', 'error');
                }
            })
            .finally(() => {
                NProgress.done();
            });
        };

        function notification($text, $icon){
            Swal.fire({
                toast: true,
                position: "top-end",
                text: $text,
                showConfirmButton: false,
                timer: 5500,
                timerProgressBar: true,
                icon: $icon,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
              });
        }

        return {
            cars,
            getFleet,
            fleetRegNumber,
            fleet,
            assign,
            assignClassRoom,
            classRoom,
            classRooms,
            getClassRooms,
            unAssignCar
        }
      }
    })
    app.mount('#student')
</script>

<script>
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var transactionId = $(this).data('transaction-id');

        Swal.fire({
            title: 'Delete Payment',
            text: 'Are you sure you want to delete payment number ' + transactionId + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>

<script>
    $('.delete-invoice').on('click', function (e) {
        e.preventDefault();
        const invoiceId = $(this).data('invoice-id'); // force string
        const form = $(this).closest('form');

        Swal.fire({
            title: 'Delete Invoice #'+ invoiceId,
            text: 'This will unenroll student from course!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endsection
