@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit administrator; {{$administrator->fname}} {{$administrator->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
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
        <div class="block block-rounded">
            <div class="block-content">
              <form action="{{ route('administrators.update', $administrator->id) }}" enctype="multipart/form-data" method="POST">
                  @method('PUT')
                @csrf
                <input type="text" name="administrator_id" id="administrator_id" value="{{$administrator->id}}" hidden>
                <h2 class="content-heading pt-0">
                  <i class="fa fa-fw fa-user text-muted me-1"></i> Administrator Information
                </h2>
                <div class="row push">
                  <div class="col-lg-4">
                    <p class="text-muted">
                      administrator details
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-5">
                    <div class="row">
                      <div class="col-6 form-floating mb-4">
                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="administrator's first name" value="{{$administrator->fname}}">
                        <label class="form-label" for="example-email-input-floating">First name</label>
                      </div>
                      <div class="col-6 form-floating mb-4">
                        <input type="text" class="form-control" id="sir_name" name="sir_name" placeholder="Sirname" value="{{$administrator->sname}}">
                        <label class="form-label" for="example-email-input-floating">Sirname</label>
                      </div>
                    </div>
                    <div class="col-4 form-floating mb-4">
                        <select class="form-select" id="gender" name="gender">
                            @foreach($genders as $value => $label)
                                <option value="{{ $value }}" {{ old('gender', $administrator->gender) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <label for="gender">Gender</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" placeholder="DDMMYY" value="{{$administrator->date_of_birth}}" formnovalidate="">
                        <label class="form-label" for="text">Date of birth</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="phone" name="phone" placeholder="+265" value="{{$administrator->phone}}">
                        <label class="form-label" for="example-email-input-floating">Phone</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="email_address" name="email" placeholder="administrator's email address" value="{{ old('email', $administrator->user?->email) }}"
                          @if(!auth()->user()->can('manage administrator accounts') || (!$administrator->user && !auth()->user()->can('assign administrator roles'))) readonly @endif
                          @if($administrator->user) required @endif>
                        <label class="form-label" for="example-email-input-floating">Email address</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="address" name="address" placeholder="Address" value="{{$administrator->address}}">
                        <label class="form-label" for="example-email-input-floating">Street address</label>
                    </div>
                    <div class="form-floating mb-4">
                      <select class="form-select" id="district" name="district">
                        @foreach ($district as $district)
                           <option value="{{$district->name}}" {{ $district->id == $administrator->district_id ? 'selected' : '' }}>{{$district->name}}</option>
                        @endforeach
                      </select>
                      <label for="district">Distirct</label>
                    </div>
                    @can('assign administrator roles')
                    @if($administrator->user || auth()->user()->can('manage administrator accounts'))
                    <div class="form-floating mb-4">
                        <select class="form-select" id="role" name="role">
                            @php
                                $currentRoles = $administrator->user?->roles->pluck('name') ?? collect();
                                $currentRole = $currentRoles->count() === 1 && $role->contains('name', $currentRoles->first())
                                    ? $currentRoles->first() : '';
                            @endphp
                            <option value="" {{ old('role', $currentRole) === '' ? 'selected' : '' }}>Keep current roles</option>
                            @foreach ($role as $role)
                                <option value="{{ $role->name }}" {{ old('role', $currentRole) === $role->name ? 'selected' : '' }}>
                                    {{ match ($role->name) {
                                        'admin' => 'Admin',
                                        'financeAdmin' => 'Finance Admin',
                                        'superAdmin' => 'Super Admin',
                                    } }}
                                </option>
                            @endforeach
                        </select>
                        <label for="role">Role</label>
                    </div>
                    @endif
                    @endcan
                  </div>
                  <div class=" content-heading"><p>&nbsp;</p></div>
                </div>
                @can('process payroll')
                <h2 class="content-heading">
                  <i class="fa fa-fw fa-user-tie text-muted me-1"></i> HRM
                </h2>
                <div class="row push">
                  <div class="col-lg-4">
                    <p class="text-muted">
                      HR department and basic salary used for payroll
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-5">
                    <div class="form-floating mb-4">
                      <select class="form-select" id="hr_department_id" name="hr_department_id">
                        <option value="" {{ old('hr_department_id', $administrator->hr_department_id) ? '' : 'selected' }}>No department</option>
                        @foreach ($hrDepartments as $hrDepartment)
                          <option value="{{ $hrDepartment->id }}" {{ old('hr_department_id', $administrator->hr_department_id) == $hrDepartment->id ? 'selected' : '' }}>
                            {{ $hrDepartment->name }}
                          </option>
                        @endforeach
                      </select>
                      <label for="hr_department_id">HR department</label>
                    </div>
                    <div class="form-floating mb-4">
                      <select class="form-select" id="designation_id" name="designation_id" onchange="prefillDesignationSalary(this)">
                        <option value="" {{ old('designation_id', $administrator->designation_id) ? '' : 'selected' }}>No designation</option>
                        @foreach ($designations as $designation)
                          <option value="{{ $designation->id }}" data-salary="{{ $designation->basic_salary }}" {{ old('designation_id', $administrator->designation_id) == $designation->id ? 'selected' : '' }}>
                            {{ $designation->name }}{{ $designation->basic_salary !== null ? ' — K' . number_format((float) $designation->basic_salary, 2) : '' }}
                          </option>
                        @endforeach
                      </select>
                      <label for="designation_id">Designation</label>
                    </div>
                    <div class="form-floating mb-4">
                      <input type="number" step="0.01" min="0" class="form-control" id="basic_salary" name="basic_salary" placeholder="0.00" value="{{ old('basic_salary', $administrator->basic_salary) }}">
                      <label class="form-label" for="basic_salary">Basic salary (K)</label>
                      <div class="form-text">Leave blank to use the designation's default salary in payroll.</div>
                    </div>
                    <script>
                      function prefillDesignationSalary(select) {
                        const salaryInput = document.getElementById('basic_salary');
                        const salary = select.options[select.selectedIndex]?.dataset.salary;
                        if (salaryInput && !salaryInput.value && salary) {
                          salaryInput.value = salary;
                        }
                      }
                    </script>
                  </div>
                </div>
                @endcan
                <x-form-actions :cancel-url="route('administrators.index')" />
              </form>
            </div>
        </div>
  </div>
  <!-- END Hero -->

@endsection
