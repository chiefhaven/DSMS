@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Administrators</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="{{ route ('addadministrator') }}" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add Administrator
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
          <div class="block-content">
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

      <div class="row">
        @foreach ($administrator as $administrator)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <!-- Profile Row (Avatar + Info) -->
                        <div class="row align-items-center mb-3">
                            <!-- Avatar Column -->
                            <div class="col-auto">
                                <img class="rounded-circle"
                                    src="{{ $administrator->avatar ?? 'media/avatars/avatar6.jpg' }}"
                                    alt="{{ $administrator->fname }}'s avatar"
                                    width="80"
                                    height="80">
                            </div>

                            <!-- Info Column -->
                            <div class="col ps-0">
                                <h5 class="mb-1">{{ $administrator->fname }} {{ $administrator->sname }}</h5>
                                <ul class="list-unstyled text-muted small mb-0">
                                    <li class="mb-1">
                                        <i class="fas fa-phone-alt me-1"></i>
                                        {{ $administrator->phone ?? 'N/A' }}
                                    </li>
                                    <li>
                                        <i class="fas fa-envelope me-1"></i>
                                        {{ $administrator->user->email ?? 'N/A' }}
                                    </li>
                                    <li>
                                        <i class="fas fa-user-tag me-1"></i>
                                        @if($administrator->user->roles->count() > 0)
                                            @php
                                                $primaryRole = $administrator->user->roles->first();
                                                $roleName = match($primaryRole->name) {
                                                    'admin' => 'Admin',
                                                    'superAdmin' => 'Super Admin',
                                                    'financeAdmin' => 'Finance Admin',
                                                    default => $primaryRole->name,
                                                };
                                            @endphp
                                            <span class="badge bg-primary">{{ $roleName }}</span>
                                            @if($administrator->user->roles->count() > 1)
                                                <span class="badge bg-secondary ms-1">+{{ $administrator->user->roles->count()-1 }}</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Staff</span>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Button at Bottom -->
                        <div class="d-grid mt-5">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle w-100"
                                        type="button"
                                        id="adminActionsDropdown"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Manage Administrator
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="adminActionsDropdown">
                                    <li>
                                        <form method="get" action="{{ url('/editadministrator', $administrator->id) }}">
                                            @csrf
                                            <button class="dropdown-item d-flex align-items-center" type="submit">
                                                <i class="fas fa-edit me-2"></i> Edit Profile
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ url('/deleteadministrator', $administrator->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item d-flex align-items-center text-danger"
                                                    onclick="return confirm('Are you sure you want to delete {{ $administrator->fname }} {{ $administrator->sname }}?')"
                                                    type="submit">
                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
      </div>

      </div>
    </div>
  <!-- END Hero -->

@endsection
