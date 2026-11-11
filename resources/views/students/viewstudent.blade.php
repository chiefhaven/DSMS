@extends('layouts.backend')

@section('css_after')
<style>
    #student .student-profile-card {
        border: 0;
        border-radius: .5rem;
        background: var(--bs-body-bg, #fff);
    }
    #student .student-profile-card > h5,
    #student .student-profile-card > h3 {
        font-size: 1rem;
        font-weight: 600;
    }
    #student .student-profile-card .table th,
    #student .student-profile-card .table td {
        padding: .75rem .5rem;
        vertical-align: middle;
    }
    #student .student-profile-card .table th {
        font-size: .875rem;
        font-weight: 500;
    }
    #student .student-profile-card .table td,
    #student .student-contact {
        overflow-wrap: anywhere;
    }
    #student .student-profile-card .rounded-pill {
        padding-left: .75rem !important;
        padding-right: .75rem !important;
    }
    #student .student-profile-card .alert .row {
        row-gap: .75rem;
    }
    #student .student-profile-card .alert .text-end {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .5rem;
    }
    #student .student-profile-card .alert .text-end .btn {
        margin: 0 !important;
    }
    #student .student-downloads {
        margin-bottom: 0;
    }
    #student .student-downloads td:last-child {
        width: 1%;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        #student .student-profile-card .alert .text-end {
            justify-content: flex-start;
        }
        #student .student-profile-card {
            padding: 1rem !important;
        }
        #student .student-profile-card .table th {
            width: 35%;
        }
    }
</style>
@endsection

@section('content')
<!-- Hero -->
<div class=""  id="student">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <a class="fs-sm text-muted" href="{{ url('/students') }}"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i>Back to students</a>
                <h1 class="fs-3 fw-semibold mt-2 mb-1">{{$student->fname}} {{$student->mname}} {{$student->sname}}</h1>
                <p class="text-muted mb-0">Student profile</p>
            </div>
            <div class="flex-shrink-0">
                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn btn-primary" id="student-actions-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-angle-down me-1" aria-hidden="true"></i><span>Actions</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0">
                            <div class="p-2">
                            @can('edit students')
                                <form method="GET" action="{{ route('students.edit', ['student' => $student->id]) }}">
                                    {{ csrf_field() }}
                                    <button class="dropdown-item nav-main-link" type="submit">
                                        <i class="nav-main-link-icon  fas fa-pencil"></i>Edit profile
                                    </button>
                                </form>
                            @endcan
                            @if (isset($student->invoice))
                                @can('manage system')
                                <button type="button" class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="nav-main-link-icon  fas fa-file-invoice"></i>Add payment
                                </button>
                                @endcan
                            @else
                                @can('create invoices')
                                    <a class="dropdown-item nav-main-link" href="{{ url('/addinvoice', $student->id) }}">
                                        <i class="nav-main-link-icon fas fa-plus-circle"></i>Enroll
                                    </a>
                                @endcan
                            @endif
                            @can('change student status')
                            <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#change-status">
                                <i class="nav-main-link-icon  fas fa-toggle-on"></i>Change status
                            </button>
                            @endcan
                            </div>
                        </div>
                    </div>
            </div>
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
                Overview
            </button>
            </li>
            @php
                $profileInvoiceRoles = auth()->user()->roles
                    ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                        || $role->permissions->contains('name', 'view invoices'))
                    ->pluck('name')->all();
            @endphp
            @role($profileInvoiceRoles)
                <li class="nav-item">
                <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" role="tab" aria-controls="invoices" aria-selected="false">
                    Invoices
                </button>
                </li>
            @endrole
            @can('manage administration')
                <li class="nav-item">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" role="tab" aria-controls="payments" aria-selected="false">
                        Payments
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="expensesList-tab" data-bs-toggle="tab" data-bs-target="#expensesList" role="tab" aria-controls="expensesList" aria-selected="false">
                        Expenses
                    </button>
                </li>
            @endcan
        </ul>
        <div class="block-content tab-content">
            <div class="tab-pane fade active show" id="student-details" role="tabpanel" aria-labelledby="student-details-tab">
                <div class="content-full">
                    <div class="row g-4 pb-4">
                        <div class="col-lg-6">
                            <div class="card student-profile-card bg-body-light p-4 h-100">
                                <!-- Profile Header -->
                                <div class="text-center mb-4">
                                    <img class="img-avatar img-avatar96 img-avatar-thumb mb-3"
                                         src="{{ $student->avatar_url ?? asset('media/avatars/avatar2.jpg') }}"
                                         alt="{{ $student->fname }}'s avatar">
                                    <h2 class="fs-4 fw-semibold mb-2">
                                        {{ $student->fname }} {{ $student->mname }} {{ $student->sname }}
                                    </h2>
                                    <span class="badge bg-primary">{{ $student->student_number ?? 'Not enrolled' }}</span>
                                </div>

                                <!-- Personal Details -->
                                <div class="mb-4">
                                    <h5 class="fs-6 fw-semibold border-bottom pb-2 mb-3">Contact &amp; identity</h5>
                                    <div class="row student-contact">
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-venus-mars me-2 text-muted"></i>
                                                <span>{{ ucfirst($student->gender) }}</span>
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
                                @can('assign students')
                                <div class="mt-auto">
                                    <h5 class="fs-6 fw-semibold border-bottom pb-2 mb-3">Vehicle</h5>

                                    @if(isset($student->invoice))
                                        @if(isset($student->fleet->car_brand_model))
                                            <div class="alert alert-warning py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-car me-3 fs-4 text-dark"></i>
                                                            <div>
                                                                <strong>{{ $student->fleet->car_registration_number }}</strong>
                                                                <div class="small text-muted">{{ $student->fleet->car_brand_model }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <button
                                                            type="button"
                                                            @click="getFleet()"
                                                            class="btn btn-sm btn-outline-warning rounded-pill px-4 me-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target=".assignCar">
                                                            <i class="fas fa-sync-alt me-1"></i> Reassign
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="unAssignCar()"
                                                            class="btn btn-sm btn-outline-danger rounded-pill px-4">
                                                            <i class="fas fa-times me-1"></i> Unassign
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-danger py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                                                        <strong>No vehicle assigned</strong>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <button
                                                            type="button"
                                                            @click="getFleet()"
                                                            class="btn btn-sm btn-primary rounded-pill px-4"
                                                            data-bs-toggle="modal"
                                                            data-bs-target=".assignCar">
                                                            <i class="fas fa-plus me-1"></i> Assign Vehicle
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted fst-italic small">
                                            Enroll to assign a vehicle.
                                        </div>
                                    @endif
                                </div>

                                @endcan
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card student-profile-card bg-body-light p-4 h-100">
                                <!-- General Information Section -->
                                <h5 class="card-title border-bottom pb-2 mb-4">Course &amp; fees</h5>

                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            @can('manage administration')
                                            <tr>
                                                <th width="40%" class="text-muted">Enrollment Date</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        {{ $student->invoice->created_at->format('j F, Y') }}
                                                    @else
                                                        @can('create invoices')
                                                        <a href="{{ url('/addinvoice', $student->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                                            <i class="fas fa-plus-circle me-1"></i> Enroll
                                                        </a>
                                                        @endcan
                                                    @endif
                                                </td>
                                            </tr>
                                            @endcan

                                            <tr>
                                                <th class="text-muted">Course</th>
                                                <td>
                                                    @if(isset($student->invoice) && isset($student->course))
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <strong>{{ $student->course->name }}</strong>
                                                                <div class="small text-muted">Licence {{ $student->course->licenceClass->class }} · {{ $student->course->duration }} days</div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Not enrolled</span>
                                                    @endif
                                                </td>
                                            </tr>

                                            @can('assign students')
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
                                                                    class="btn btn-sm btn-outline-warning rounded-pill px-4"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target=".assignClassRoom">
                                                                <i class="fas fa-sync-alt me-1"></i> Reassign
                                                            </button>
                                                        </div>
                                                    @else
                                                        <button type="button"
                                                                @click="getClassRooms()"
                                                                class="btn btn-sm btn-primary rounded-pill px-4"
                                                                data-bs-toggle="modal"
                                                                data-bs-target=".assignClassRoom">
                                                            <i class="fas fa-plus me-1"></i> Assign
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endcan

                                            <!-- Payment Information -->
                                            @can('manage administration')
                                            <tr class="border-top">
                                                <th class="text-muted">Fees</th>
                                                <td>
                                                    @if(isset($student->invoice->created_at))
                                                        <span class="fw-bold">K{{ number_format($student->invoice->invoice_total) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Paid</th>
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
                                            @endcan
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Attendance Progress -->
                                <div class="mt-4">
                                    <h5 class="fs-6 border-bottom pb-2 mb-3 fw-semibold">Attendance</h5>
                                    @php
                                        // Normalize attendance percent
                                        $attendancePercent = max(0, min(100, round($attendancePercent ?? 0, 2)));

                                        if ($attendancePercent < 50) {
                                            $badgeColor = 'danger';
                                            $statusLabel = 'Needs Improvement';
                                        } elseif ($attendancePercent < 80) {
                                            $badgeColor = 'warning';
                                            $statusLabel = 'In Progress';
                                        } else {
                                            $badgeColor = 'success';
                                            $statusLabel = 'On Track';
                                        }

                                        if ($student->status === 'Completed' || $student->status === 'Finished') {
                                            $badgeColor = 'primary';
                                            $statusLabel = 'Completed';
                                        }
                                    @endphp
                                    <div class="d-flex flex-wrap gap-2 justify-content-between mb-2">
                                        <span class="badge bg-{{ $badgeColor }} px-1 py-1 fs-6 shadow-sm small">
                                            {{ $statusLabel }} — {{ $attendancePercent }}%
                                        </span>
                                        <span class="text-muted small">
                                            {{ $attendanceTheoryCount }} theory / {{ $attendancePracticalCount }} practical of {{ $courseDuration }}
                                        </span>
                                    </div>

                                    <div class="progress" style="height: 10px;">
                                        <div
                                            class="progress-bar bg-{{ $badgeColor }} progress-bar-striped progress-bar-animated"
                                            role="progressbar"
                                            style="width: {{ $attendancePercent }}%;"
                                            aria-valuenow="{{ $attendancePercent }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <!-- Course Status -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="fs-6 mb-0 text-muted fw-semibold">Status</h5>
                                        <span class="badge bg-primary-light text-primary px-3 py-2">
                                            {{ ucfirst($student->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @canany(['generate student letters', 'generate student reports'])
                        <div class="col-12">
                            <div class="card student-profile-card bg-body-light p-4">
                                <h3 class="border-bottom pb-2 mb-3">Downloads</h3>
                                @php
                                    $studentDownloads = [
                                        ['Traffic Register Card-Reference', '/trafic-card-reference-letter'],
                                        ['Highway code 1 Aptitude Reference Letter', '/aptitude-test-reference-letter'],
                                        ['Highway code 2 Aptitude Reference Letter', '/second-aptitude-test-reference-letter'],
                                        ['Road Test Reference Letter', '/final-test-reference-letter'],
                                        ['G and D Aptitude Test Reference Letter', '/g-and-d-aptitude-test-reference-letter'],
                                        ['G and D Road Test Reference Letter', '/g-and-d-road-test-reference-letter'],
                                        ['Category P Aptitude Test Reference Letter', '/category-p-aptitude-test-reference-letter'],
                                        ['Category P Road Test Reference Letter', '/category-p-road-test-reference-letter'],
                                        ['Lesson Attendance Report', '/lesson-report'],
                                    ];
                                @endphp
                                <table class="table student-downloads">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Description</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($studentDownloads as $index => [$description, $path])
                                            @if (($path === '/lesson-report' && auth()->user()->can('generate student reports')) || ($path !== '/lesson-report' && auth()->user()->can('generate student letters')))
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $description }}</td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ url($path, $student->id) }}">
                                                        @csrf
                                                        <button class="btn btn-primary rounded-pill px-4" type="submit">Download</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endcanany
                        </div>
                    </div>
                </div>
            </div>
        @include('students.partials.invoicesPayments')
        @include('students.partials.studentExpensesList')
    </div>
    </div>
    </div>

    @can('manage administration')
        @if (isset($student->invoice))
        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <!-- Header -->
                    <div class="block-header modal-header bg-gradient-info p-4">
                        <h5 class="modal-title text-white fs-5 fw-bold">
                            <i class="fas fa-credit-card me-2"></i>Add Payment
                        </h5>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4">
                        <form @submit.prevent="submitPaymentForm" enctype="multipart/form-data">
                            @csrf

                            <!-- Error Display -->
                            <div id="formErrors" class="alert alert-danger d-none">
                                <ul class="mb-0" id="errorList"></ul>
                            </div>

                            @if(isset($student->invoice->created_at))
                                <input type="hidden" name="invoice_id" :value="paymentForm.invoice_id">
                            @endif

                            <!-- Date Field -->
                            <div class="mb-4">
                                <label for="date_created" class="form-label text-muted">
                                    Payment Date
                                </label>
                                <input type="date" class="form-control border-2 rounded-3 py-3"
                                    id="date_created" name="date_created" v-model="paymentForm.date" required>
                            </div>

                            <!-- Amount and Method Row -->
                            <div class="row g-3 mb-4">
                                <!-- Amount -->
                                <div class="col-md-6">
                                    <label for="paid_amount" class="form-label text-muted">
                                        Amount (MMK)
                                    </label>
                                    <input type="number" class="form-control border-2 rounded-3"
                                        id="paid_amount" name="paid_amount" v-model="paymentForm.paid_amount"
                                        min="0" step="0.01" required>
                                </div>

                                <!-- Payment Method -->
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label text-muted">
                                        Payment Method
                                    </label>
                                    <select class="form-select border-2 rounded-3 py-3"
                                        id="payment_method"
                                        name="payment_method"
                                        v-model="paymentForm.payment_method"
                                        required>
                                        <option disabled value="">-- Select Method --</option>
                                        <option
                                            v-for="paymentMethod in paymentMethods"
                                            :key="paymentMethod.id"
                                            :value="paymentMethod.id">
                                            @{{ paymentMethod.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Conditional Fields -->
                            <div class="row g-3 mb-4" v-if="paymentForm.payment_method != cashPaymentId">
                                <!-- Transaction number -->
                                <div class="col-md-12">
                                    <label for="transaction_number" class="form-label text-muted">
                                        Transaction/Reference no.
                                    </label>
                                    <input type="text" class="form-control border-2 rounded-3"
                                        id="transaction_number" name="transaction_number"
                                        v-model="paymentForm.transaction_number" required>
                                </div>

                                <!-- Payment Proof -->
                                <div class="mb-4">
                                    <label for="payment_proof" class="form-label text-muted">
                                        Payment Proof
                                    </label>
                                    <input type="file"
                                        class="form-control border-2 rounded-3"
                                        id="payment_proof"
                                        name="payment_proof"
                                        @change="handleFileUpload"
                                        accept="image/*,.pdf,.doc,.docx"
                                        required>
                                    <div class="form-text">Accepted: JPG, PNG, PDF, DOC (Max 5MB)</div>
                                </div>
                            </div>

                            <!-- Footer Buttons -->
                            <x-form-actions modal />
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endcan


    @can('assign students')
        @include('students.partials.assignCarModal')
        @include('students.partials.assignClassRoomModal')
    @endcan
</div>

@can('change student status')
    @include('students.partials.changeStatus')
@endcan

<script setup>

    const viewStudent = createApp({
      setup() {

        const cars = ref([])
        const fleetRegNumber = ref(null)
        const fleet = ref('')
        const classRoom = ref('{{ $student->classroom->id ?? null }}');
        const studentCourseClass = ref('{{ $student->course->licence_class_id ?? null }}');
        const classRooms = ref([]);
        const paymentMethods = ref([]);
        const cashPaymentId = ref(null)
        const paymentForm = ref({
            payment_method: null,
            invoice_id: '{{ $student->invoice->id ?? null }}',
            amount: "",
            date:"",
            payment_proof:"",
            wantsJson: true,
        });

        onMounted(() => {
            getPaymentMethods()
        })

        // Fetch fleet vehicles
        const getFleet = async () => {
            try {
                NProgress.start();

                const response = await axios.get('/getFleet', {
                    params: {
                      studentCourseClass: studentCourseClass.value
                    }
                });

                if (response.status === 200) {
                    cars.value = response.data;
                } else {
                    notification('Received unexpected response format', 'warning');
                }

            } catch (error) {
                if (error.response) {
                    // Server responded with error status
                    if (error.response.data.errors) {
                        const errorMessages = Object.values(error.response.data.errors)
                            .flat()
                            .join('\n');
                        notification(errorMessages, 'error', 5000);
                    } else {
                        notification(error.response.data.message || 'Failed to load fleet data', 'error');
                    }
                } else if (error.request) {
                    // No response received
                    notification('Network error - please check your connection', 'error');
                } else {
                    // Request setup error
                    notification(`Error: ${error.message}`, 'error');
                }
                return false;
            } finally {
                NProgress.done();
            }
        };

        // Fetch classrooms with consistent error handling
        const getClassRooms = async () => {
            try {
                NProgress.start();

                const response = await axios.get('/getClassRooms');

                if (response.status === 200 && response.data) {
                    classRooms.value = response.data;
                } else {
                    notification('Received empty or invalid classroom data', 'warning');
                }

            } catch (error) {
                if (error.response) {
                    // Server responded with error status
                    if (error.response.data.errors) {
                        const errorMessages = Object.values(error.response.data.errors)
                            .flat()
                            .join('\n');
                        notification(errorMessages, 'error', 5000);
                    } else {
                        notification(error.response.data.message || 'Failed to load classrooms', 'error');
                    }
                } else if (error.request) {
                    // No response received
                    notification('Network error - please check your connection', 'error');
                } else {
                    // Request setup error
                    notification(`Error: ${error.message}`, 'error');
                }

                return false;
            } finally {
                NProgress.done();
            }
        };

        const assign = async () => {
            try {
                NProgress.start();

                const response = await axios.post('/assignCar', {
                    student: '{{ $student->id }}',
                    fleet: fleetRegNumber.value
                });

                if (response.status === 200) {
                    notification(response.data.message || 'Assignment successful', 'success');
                    setTimeout(() => location.reload(), 1500); // Delay reload to show notification
                } else {
                    notification(response.data.message || 'Unknown response from server', 'warning');
                }

            } catch (error) {
                if (error.response) {
                    // Server responded with error status (4xx, 5xx)
                    if (error.response.data.errors) {
                        // Laravel validation errors
                        const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                        notification(errorMessages, 'error');
                    } else {
                        notification(error.response.data.message || 'Request failed', 'error');
                    }
                } else if (error.request) {
                    // No response received
                    notification('No response from server. Please check your connection.', 'error');
                } else {
                    // Something wrong in request setup
                    notification('Error: ' + error.message, 'error');
                }
            } finally {
                NProgress.done();
            }
        };

        const unAssignCar = async () => {
            try {
                NProgress.start();

                const response = await axios.post('/unAssignCar', {
                    student: '{{ $student->id }}',
                    fleet: fleetRegNumber.value
                });

                if (response.status === 200) {
                    notification(response.data.message || 'Car unassigned successfully', 'success');
                    setTimeout(() => location.reload(), 1200); // Delay reload to show notification
                } else {
                    notification(response.data.message || 'Operation completed with unexpected response', 'warning');
                }

            } catch (error) {
                // Handle different types of errors
                if (error.response) {
                    // Server responded with error status (4xx, 5xx)
                    if (error.response.data.errors) {
                        // Laravel validation errors
                        const errorMessages = Object.values(error.response.data.errors)
                            .flat()
                            .join('<br>'); // Use <br> for HTML notifications
                        notification(errorMessages, 'error', 5000); // Longer display for multiple errors
                    } else {
                        notification(error.response.data.message || 'Failed to unassign car', 'error');
                    }
                } else if (error.request) {
                    // The request was made but no response received
                    notification('Network error - please check your connection', 'error');
                } else {
                    // Something happened in setting up the request
                    notification('Error: ' + error.message, 'error');
                }

            } finally {
                NProgress.done();
            }
        };

        const assignClassRoom = async () => {
            try {
                NProgress.start();

                const response = await axios.post('/assign-class-room', {
                    student: '{{ $student->id }}',
                    classroom: classRoom.value
                });

                // Successful response
                if (response.status === 200) {
                    const message = response.data.message || 'Classroom assigned successfully';
                    notification(message, 'success');

                    // Delay reload to allow user to see the success message
                    setTimeout(() => location.reload(), 1500);
                } else {
                    notification(response.data.message || 'Operation completed with unexpected response', 'warning');
                }

            } catch (error) {
                // Handle different types of errors
                if (error.response) {
                    // Server responded with error status (4xx, 5xx)
                    if (error.response.data.errors) {
                        // Handle Laravel validation errors
                        const errorMessages = Object.values(error.response.data.errors)
                            .flat()
                            .join('\n');
                        notification(errorMessages, 'error', 5000); // Show for 5 seconds
                    } else if (error.response.data.message) {
                        notification(error.response.data.message, 'error');
                    } else {
                        notification(`Request failed with status ${error.response.status}`, 'error');
                    }
                } else if (error.request) {
                    // The request was made but no response received
                    notification('Network error - please check your connection', 'error');
                } else {
                    // Something happened in setting up the request
                    notification(`Error: ${error.message}`, 'error');
                }

            } finally {
                NProgress.done();
            }
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
        };

        const handleFileUpload = async(event) => {
            paymentForm.value.payment_proof = event.target.files[0];
        };

        const submitPaymentForm = async () => {
            NProgress.start();
            const formData = new FormData();

            for (let key in paymentForm.value) {
                formData.append(key, paymentForm.value[key]);
            }


            try {

                const response = await axios.post('/add-payment', formData, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Content-Type': 'multipart/form-data',
                    },
                });

                if (response.status === 200) {
                    //close modal
                    $('#paymentModal').modal('hide');

                    //Show success toast or alert
                    notification('The payment was added successfully!', 'success')

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);

                    //Reset form fields
                    paymentForm.value = {
                        payment_method: null,
                        invoice_id: '{{ $student->invoice->id ?? null }}',
                        amount: "",
                        date: "",
                        payment_proof: "",
                        wantsJson: true,
                    };
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.message;
                    notification(errors, 'error')
                } else {
                    // Handle other errors
                    if (error.response) {
                        // Server responded with error status
                        if (error.response.data.errors) {
                            const errorMessages = Object.values(error.response.data.errors)
                                .flat()
                                .join('\n');
                            notification(errorMessages, 'error', 5000);
                        } else {
                            notification(error.response.data.message || 'Failed to submit payment form', 'error');
                        }
                    } else if (error.request) {
                        // No response received
                        notification('Network error - please check your connection', 'error');
                    } else {
                        // Request setup error
                        notification(`Error: ${error.message}`, 'error');
                    }
                }
            } finally{
                NProgress.done()
            }
        };


        const getPaymentMethods = async () => {
            try {
                NProgress.start();

                const response = await axios.get('/api/getPaymentMethods');

                if (response.status === 200) {
                    paymentMethods.value = response.data;

                    const cash = paymentMethods.value.find(pm => pm.name === "Cash");

                    if (cash) {
                        cashPaymentId.value = cash.id;
                        paymentForm.value.payment_method = cashPaymentId.value;
                    }

                } else {
                    notification('Received unexpected response format', 'warning');
                }

            } catch (error) {
                if (error.response) {
                    // Server responded with error status
                    if (error.response.data.errors) {
                        const errorMessages = Object.values(error.response.data.errors)
                            .flat()
                            .join('\n');
                        notification(errorMessages, 'error', 5000);
                    } else {
                        notification(error.response.data.message || 'Failed to load payment methods', 'error');
                    }
                } else if (error.request) {
                    // No response received
                    notification('Network error - please check your connection', 'error');
                } else {
                    // Request setup error
                    notification(`Error: ${error.message}`, 'error');
                }

                return false;
            } finally {
                NProgress.done();
            }
        };

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
            unAssignCar,
            paymentForm,
            handleFileUpload,
            submitPaymentForm,
            paymentMethods,
            cashPaymentId,
        }
      }
    })
    viewStudent.mount('#student')
</script>

<script>
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var transactionId = $(this).data('transaction-id');

        Swal.fire({
            title: 'Delete Payment',
            text: 'Are you sure you want to delete transaction number ' + transactionId + '?',
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
