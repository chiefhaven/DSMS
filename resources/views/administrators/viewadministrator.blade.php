@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <div>
                    <a href="{{ route('administrators.index') }}" class="fs-sm text-muted"><i class="fa fa-arrow-left me-1"></i> Administrators</a>
                    <h1 class="fs-3 fw-semibold my-2">{{ $administrator->fname }} {{ $administrator->sname }}</h1>
                </div>
                @can('edit administrators')
                <div class="dropdown">
                    <button class="btn btn-primary rounded-pill px-4 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('administrators.edit', $administrator->id) }}"><i class="fa fa-edit me-2"></i>Edit Profile</a>
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <div class="content content-full" id="administrator">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-panel"
                        type="button" role="tab" aria-controls="profile-panel" aria-selected="true">Profile</button>
            </li>
            @can('manage administrator accounts')
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-panel"
                            type="button" role="tab" aria-controls="account-panel" aria-selected="false">Account management</button>
                </li>
            @endcan
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="profile-panel" role="tabpanel" aria-labelledby="profile-tab">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <img class="img-avatar img-avatar96 img-avatar-thumb" src="{{ asset('media/avatars/avatar2.jpg') }}" alt="Administrator avatar">
                                    <div>
                                        <h2 class="fs-4 fw-semibold mb-2">{{ $administrator->fname }} {{ $administrator->sname }}</h2>
                                        @if ($administrator->User)
                                            <span class="badge bg-{{ $administrator->User->is_active ? 'success' : 'secondary' }}">
                                                {{ $administrator->User->is_active ? 'Login enabled' : 'Login disabled' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No linked account</span>
                                        @endif
                                    </div>
                                </div>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3 text-muted">Phone</dt><dd class="col-sm-9">{{ $administrator->phone ?: '-' }}</dd>
                                    <dt class="col-sm-3 text-muted">Email</dt><dd class="col-sm-9 text-break">{{ $administrator->User?->email ?? '-' }}</dd>
                                    <dt class="col-sm-3 text-muted">Address</dt><dd class="col-sm-9 mb-0">{{ $administrator->address ?: '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-semibold text-uppercase text-muted mb-3">Roles</h2>
                                @forelse ($administrator->User?->roles ?? [] as $role)
                                    <span class="badge bg-primary me-1 mb-2">
                                        {{ match ($role->name) {
                                            'admin' => 'Admin',
                                            'superAdmin' => 'Super Admin',
                                            'financeAdmin' => 'Finance Admin',
                                            default => ucfirst($role->name),
                                        } }}
                                    </span>
                                @empty
                                    <span class="badge bg-secondary">Staff</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @can('manage administrator accounts')
                <div class="tab-pane fade" id="account-panel" role="tabpanel" aria-labelledby="account-tab">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-bottom py-3 px-4">
                            <h2 class="fs-6 fw-semibold mb-0">User account</h2>
                        </div>
                        <div class="card-body p-4">
                            <x-staff-account-form :user="$administrator->User" profile-label="administrator" />
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
    @can('manage administrator accounts')
    <script>
        createApp({
            setup() {
                @include('components.staff-account-state', [
                    'accountUser' => $administrator->User,
                    'accountUpdateUrl' => route('administrators.account.update', $administrator->id),
                ])
                return { account, accountSaving, accountErrors, accountMessage, accountError, saveAccount, resetAccount };
            }
        }).mount('#administrator');
    </script>
    @endcan
@endsection
