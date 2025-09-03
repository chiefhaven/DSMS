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
              <form action="{{ url('/updateadministrator') }}" enctype="multipart/form-data" method="POST">
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
                      <input type="text" class="form-control" id="email_address" name="email" placeholder="administrator's email address" value="{{$administrator->user?->email}}" required>
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
                    <div class="form-floating mb-4">
                        <select class="form-select" id="role" name="role">
                            @foreach ($role as $role)
                                    <option value="{{ $role->name }}"
                                        {{ $administrator->user?->roles?->contains('name', $role->name) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                    @switch($role->name)
                                        @case('admin')
                                            Admin
                                            @break
                                        @case('financeAdmin')
                                            Finance Admin
                                            @break
                                        @case('superAdmin')
                                            Super Admin
                                            @break
                                        @default
                                            {{ ucfirst($role->name) }}
                                    @endswitch
                                </option>
                            @endforeach
                        </select>
                      <label for="district">Role</label>
                    </div>
                  </div>
                  <div class=" content-heading"><p>&nbsp;</p></div>
                    {{--  <div class="col-lg-4">
                        <p class="text-muted">
                        Login details
                        </p>
                    </div>
                    <div class="col-lg-8 col-xl-5">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Administrator's username">
                            <label class="form-label" for="example-email-input-floating">Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="password">
                            <label class="form-label" for="example-email-input-floating">Password</label>
                        </div>
                    </div>  --}}
                </div>
                <div class="row push">
                  <div class="col-lg-8 col-xl-5 offset-lg-4">
                    <div class="mb-4">
                      <button type="submit" class="btn btn-alt-primary">
                        <i class="fa fa-check-circle opacity-50 me-1"></i> Update administrator
                      </button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
        </div>
  </div>
  <!-- END Hero -->

@endsection
