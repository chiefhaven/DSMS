@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Administrator</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if (Session::has('message'))
            <div class="alert alert-success">{{ Session::get('message') }}</div>
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

        <form action="{{ route('administrators.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row g-4">
                <div class="col-12">
                    <section class="mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true"><i class="fa fa-user"></i></span>
                            <h3 class="fs-5 fw-semibold mb-0">Personal information</h3>
                        </div>
                        <div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="First name" required>
                                        <label for="first_name">First name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control @error('sir_name') is-invalid @enderror" id="sir_name" name="sir_name" value="{{ old('sir_name') }}" placeholder="Surname" required>
                                        <label for="sir_name">Surname</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                            <option value="female" {{ old('gender', 'female') === 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <label for="gender">Gender</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text"
                                               class="form-control js-datepicker @error('date_of_birth') is-invalid @enderror"
                                               id="date_of_birth"
                                               name="date_of_birth"
                                               placeholder="YYYY-MM-DD"
                                               autocomplete="off"
                                               value="{{ old('date_of_birth') }}"
                                               required>
                                        <label for="date_of_birth">Date of birth</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+265" required>
                                        <label for="phone">Phone</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select @error('district') is-invalid @enderror" id="district" name="district" required>
                                            @foreach ($district as $item)
                                                <option value="{{ $item->name }}" {{ old('district') === $item->name ? 'selected' : '' }}>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <label for="district">District</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" placeholder="Street address" required>
                                        <label for="address">Street address</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true"><i class="fa fa-briefcase"></i></span>
                            <h3 class="fs-5 fw-semibold mb-0">HRM &amp; employment</h3>
                        </div>
                        <div>
                            <p class="text-muted fs-sm mb-3">Employment dates, contract type, and terms of employment.</p>
                            @include('hrm.partials.employment-details', [
                                'employee' => null,
                                'statusOptions' => ['Active', 'Pending', 'Suspended', 'Contract ended'],
                                'statusSelected' => old('status', 'Active'),
                                'statusRequired' => true,
                            ])
                            @include('hrm.partials.terms-of-employment', ['employee' => null, 'staffType' => 'administrator'])

                            <div class="row g-3 mt-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100 d-flex align-items-center">
                                        <div class="form-check mb-0">
                                            <input type="hidden" name="paye" value="0">
                                            <input class="form-check-input @error('paye') is-invalid @enderror" type="checkbox" value="1" id="paye" name="paye"
                                                   {{ old('paye') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="paye">PAYE applicable</label>
                                            <div class="form-text mb-0">Enable PAYE calculations for this employee on payroll.</div>
                                            @error('paye')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="border rounded p-3 h-100 d-flex align-items-center">
                                        <div class="form-check mb-0">
                                            <input type="hidden" name="pension_applicable" value="0">
                                            <input class="form-check-input @error('pension_applicable') is-invalid @enderror" type="checkbox" value="1" id="pension_applicable" name="pension_applicable"
                                                   {{ old('pension_applicable') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pension_applicable">Pension applicable</label>
                                            <div class="form-text mb-0">Enable pension deductions/contributions for this employee on payroll.</div>
                                            @error('pension_applicable')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('hrm.partials.bank-account-details', ['employee' => null])
                        </div>
                    </section>
                </div>

                <div class="col-12">
                    <section class="mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true"><i class="fa fa-lock"></i></span>
                            <h3 class="fs-5 fw-semibold mb-0">Account &amp; access</h3>
                        </div>
                        <div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email_address" name="email" value="{{ old('email') }}" placeholder="Email address" required>
                                <label for="email_address">Email address</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" placeholder="Username" required>
                                <label for="username">Username</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            <div class="form-floating">
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    @foreach ($role as $item)
                                        <option value="{{ $item->name }}" {{ old('role') === $item->name ? 'selected' : '' }}>{{ match ($item->name) {
                                            'admin' => 'Admin',
                                            'financeAdmin' => 'Finance Admin',
                                            'superAdmin' => 'Super Admin',
                                            default => ucfirst($item->name),
                                        } }}</option>
                                    @endforeach
                                </select>
                                <label for="role">Role</label>
                            </div>
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

@push('scripts')
    <script>
        $(function () {
            if ($.fn.datepicker) {
                $('#date_of_birth').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
            }
        });
    </script>
@endpush
