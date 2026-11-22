
@extends('layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">
                    Edit Administrator: {{ $administrator->fname }} {{ $administrator->sname }}
                </h1>

                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
            </div>
        </div>
    </div>

    <div class="content content-full">

        @if (Session::has('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('administrators.update', $administrator->id) }}" method="POST" autocomplete="off">
            @method('PUT')
            @csrf

            <input type="hidden" name="administrator_id" id="administrator_id" value="{{ $administrator->id }}">

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row g-4">

                        {{-- Personal Information --}}
                        <div class="col-12">
                            <section class="mb-4">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                                        <i class="fa fa-user"></i>
                                    </span>

                                    <h3 class="fs-5 fw-semibold mb-0">Personal information</h3>
                                </div>

                                <div class="row g-3">

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control @error('first_name') is-invalid @enderror"
                                                id="first_name"
                                                name="first_name"
                                                value="{{ old('first_name', $administrator->fname) }}"
                                                placeholder="First name"
                                                required
                                            >
                                            <label for="first_name">First name</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control @error('sir_name') is-invalid @enderror"
                                                id="sir_name"
                                                name="sir_name"
                                                value="{{ old('sir_name', $administrator->sname) }}"
                                                placeholder="Surname"
                                                required
                                            >
                                            <label for="sir_name">Surname</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select
                                                class="form-select @error('gender') is-invalid @enderror"
                                                id="gender"
                                                name="gender"
                                                required
                                            >
                                                @foreach ($genders as $value => $label)
                                                    <option value="{{ $value }}" {{ old('gender', $administrator->gender) === $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="gender">Gender</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control js-datepicker @error('date_of_birth') is-invalid @enderror"
                                                id="date_of_birth"
                                                name="date_of_birth"
                                                placeholder="YYYY-MM-DD"
                                                autocomplete="off"
                                                value="{{ old('date_of_birth', isset($administrator->date_of_birth) ? \Illuminate\Support\Carbon::parse($administrator->date_of_birth)->format('Y-m-d') : '') }}"
                                                required
                                            >
                                            <label for="date_of_birth">Date of birth</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input
                                                type="tel"
                                                class="form-control @error('phone') is-invalid @enderror"
                                                id="phone"
                                                name="phone"
                                                value="{{ old('phone', $administrator->phone) }}"
                                                placeholder="+265"
                                                required
                                            >
                                            <label for="phone">Phone</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select
                                                class="form-select @error('district') is-invalid @enderror"
                                                id="district"
                                                name="district"
                                                required
                                            >
                                                @foreach ($district as $item)
                                                    <option
                                                        value="{{ $item->name }}"
                                                        {{ old('district', $item->id == $administrator->district_id ? $item->name : '') === $item->name ? 'selected' : '' }}
                                                    >
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="district">District</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control @error('address') is-invalid @enderror"
                                                id="address"
                                                name="address"
                                                value="{{ old('address', $administrator->address) }}"
                                                placeholder="Street address"
                                                required
                                            >
                                            <label for="address">Street address</label>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            {{-- HRM & Employment --}}
                            <section class="mb-4">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                                        <i class="fa fa-briefcase"></i>
                                    </span>

                                    <h3 class="fs-5 fw-semibold mb-0">HRM &amp; employment</h3>
                                </div>

                                <p class="text-muted fs-sm mb-3">
                                    Employment dates, contract type, and terms of employment.
                                </p>

                                {{-- Employment Details --}}
                                @include('hrm.partials.employment-details', [
                                    'employee' => $administrator,
                                    'statusOptions' => ['Active', 'Pending', 'Suspended', 'Contract ended'],
                                    'statusSelected' => old('status', $administrator->status ?? 'Active'),
                                    'statusRequired' => true,
                                ])

                                @include('hrm.partials.terms-of-employment', [
                                    'employee' => $administrator,
                                    'staffType' => 'administrator',
                                ])

                                @can('process payroll')

                                    <div class="row g-3 mt-4">

                                        {{-- HR Department --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select
                                                    class="form-select"
                                                    id="hr_department_id"
                                                    name="hr_department_id"
                                                    onchange="filterDesignationOptions(true)"
                                                >
                                                    <option value="" {{ old('hr_department_id', $administrator->hr_department_id) ? '' : 'selected' }}>
                                                        No department
                                                    </option>

                                                    @foreach ($hrDepartments as $hrDepartment)
                                                        <option
                                                            value="{{ $hrDepartment->id }}"
                                                            {{ old('hr_department_id', $administrator->hr_department_id) == $hrDepartment->id ? 'selected' : '' }}
                                                        >
                                                            {{ $hrDepartment->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <label for="hr_department_id">HR department</label>
                                            </div>
                                        </div>

                                        {{-- Designation --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select
                                                    class="form-select"
                                                    id="designation_id"
                                                    name="designation_id"
                                                >
                                                    <option value="" {{ old('designation_id', $administrator->designation_id) ? '' : 'selected' }}>
                                                        No designation
                                                    </option>

                                                    @foreach ($designations as $designation)
                                                        <option
                                                            value="{{ $designation->id }}"
                                                            data-department="{{ $designation->hr_department_id }}"
                                                            data-current="{{ old('designation_id', $administrator->designation_id) == $designation->id ? 'true' : 'false' }}"
                                                            {{ old('designation_id', $administrator->designation_id) == $designation->id ? 'selected' : '' }}
                                                        >
                                                            {{ $designation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <label for="designation_id">Designation</label>
                                            </div>
                                        </div>

                                        {{-- Salary Grade --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select
                                                    class="form-select"
                                                    id="salary_grade_id"
                                                    name="salary_grade_id"
                                                    onchange="setBasicSalaryFromGrade(this)"
                                                >
                                                    <option value="" {{ old('salary_grade_id', $administrator->salary_grade_id) ? '' : 'selected' }}>
                                                        No salary grade
                                                    </option>

                                                    @foreach ($salaryGrades as $salaryGrade)
                                                        <option
                                                            value="{{ $salaryGrade->id }}"
                                                            data-salary="{{ $salaryGrade->basic_salary }}"
                                                            {{ old('salary_grade_id', $administrator->salary_grade_id) == $salaryGrade->id ? 'selected' : '' }}
                                                        >
                                                            {{ $salaryGrade->code }} — {{ $salaryGrade->name }}
                                                            (K{{ number_format((float) $salaryGrade->basic_salary, 2) }})
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <label for="salary_grade_id">Salary grade</label>
                                            </div>
                                        </div>

                                        {{-- Basic Salary --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">

                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    id="basic_salary"
                                                    name="basic_salary"
                                                    value="{{ old('basic_salary', $administrator->basic_salary ?? $administrator->salaryGrade?->basic_salary) }}"
                                                    placeholder="0.00"
                                                >

                                                <label for="basic_salary">Basic salary (K)</label>

                                            </div>
                                        </div>

                                        {{-- PAYE --}}
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 h-100 d-flex align-items-center">
                                                <div class="form-check mb-0">
                                                    <input type="hidden" name="paye" value="0">
                                                    <input class="form-check-input @error('paye') is-invalid @enderror" type="checkbox" value="1" id="paye" name="paye"
                                                           {{ old('paye', (bool) ($administrator->paye ?? false)) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="paye">PAYE applicable</label>
                                                    @error('paye')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Pension --}}
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 h-100 d-flex align-items-center">
                                                <div class="form-check mb-0">
                                                    <input type="hidden" name="pension_applicable" value="0">
                                                    <input class="form-check-input @error('pension_applicable') is-invalid @enderror" type="checkbox" value="1" id="pension_applicable" name="pension_applicable"
                                                           {{ old('pension_applicable', (bool) ($administrator->pension_applicable ?? false)) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="pension_applicable">Pension applicable</label>
                                                    @error('pension_applicable')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <script>
                                        function filterDesignationOptions(clearInvalidSelection) {
                                            const departmentSelect = document.getElementById('hr_department_id');
                                            const designationSelect = document.getElementById('designation_id');

                                            const selectedOption = designationSelect.options[designationSelect.selectedIndex];

                                            if (
                                                clearInvalidSelection &&
                                                selectedOption.value &&
                                                selectedOption.dataset.department !== departmentSelect.value
                                            ) {
                                                designationSelect.value = '';
                                            }

                                            Array.from(designationSelect.options).forEach((option) => {
                                                const matchesDepartment =
                                                    option.dataset.department === departmentSelect.value;

                                                const keepCurrent =
                                                    !clearInvalidSelection &&
                                                    option.dataset.current === 'true';

                                                option.hidden =
                                                    Boolean(option.value) &&
                                                    !matchesDepartment &&
                                                    !keepCurrent;
                                            });
                                        }

                                        function setBasicSalaryFromGrade(select) {
                                            const salary = select.options[select.selectedIndex]?.dataset.salary;

                                            if (salary !== undefined) {
                                                document.getElementById('basic_salary').value = salary;
                                            }
                                        }

                                        filterDesignationOptions(false);
                                    </script>

                                @endcan


                                @include('hrm.partials.bank-account-details', [
                                    'employee' => $administrator
                                ])
                            </section>
                        </div>

                        {{-- Account & Access --}}
                        <div class="col-12">
                            <section class="mb-4">

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                                        <i class="fa fa-lock"></i>
                                    </span>

                                    <h3 class="fs-5 fw-semibold mb-0">Account &amp; access</h3>
                                </div>

                                <div>
                                    <div class="form-floating mb-3">
                                        <input
                                            type="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            id="email_address"
                                            name="email"
                                            value="{{ old('email', $administrator->user?->email) }}"
                                            placeholder="Email address"
                                            @if (!auth()->user()->can('manage administrator accounts') || (!$administrator->user && !auth()->user()->can('assign administrator roles'))) readonly @endif
                                            @if ($administrator->user) required @endif
                                        >

                                        <label for="email_address">Email address</label>
                                    </div>

                                    @can('assign administrator roles')

                                        @if ($administrator->user || auth()->user()->can('manage administrator accounts'))

                                            <div class="form-floating">

                                                @php
                                                    $currentRoles = $administrator->user?->roles->pluck('name') ?? collect();

                                                    $currentRole = $currentRoles->count() === 1 && $role->contains('name', $currentRoles->first())
                                                        ? $currentRoles->first()
                                                        : '';
                                                @endphp

                                                <select class="form-select" id="role" name="role">

                                                    <option value="" {{ old('role', $currentRole) === '' ? 'selected' : '' }}>
                                                        Keep current roles
                                                    </option>

                                                    @foreach ($role as $item)
                                                        <option
                                                            value="{{ $item->name }}"
                                                            {{ old('role', $currentRole) === $item->name ? 'selected' : '' }}
                                                        >
                                                            {{ match ($item->name) {
                                                                'admin' => 'Admin',
                                                                'financeAdmin' => 'Finance Admin',
                                                                'superAdmin' => 'Super Admin',
                                                                default => ucfirst($item->name),
                                                            } }}
                                                        </option>
                                                    @endforeach

                                                </select>

                                                <label for="role">Role</label>
                                            </div>

                                        @endif

                                    @endcan
                                </div>
                            </section>
                        </div>

                    </div>
                </div>
            </div>

            <x-form-actions :cancel-url="route('administrators.index')" />

        </form>
    </div>

@endsection
