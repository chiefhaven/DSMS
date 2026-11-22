@extends('layouts.backend')

@section('css_after')
    <style>
        #administrator .block {
            margin-bottom: .75rem;
        }
        #administrator .nav-tabs-block .nav-link {
            padding: .55rem .85rem;
        }
        #administrator .block-content.tab-content {
            padding: .75rem;
        }
        .swal2-popup.administrator-account-dialog {
            font-family: inherit;
            font-size: 1rem;
        }
        .swal2-popup.administrator-account-dialog .swal2-title {
            font-family: inherit;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .swal2-popup.administrator-account-dialog .swal2-html-container,
        .swal2-popup.administrator-account-dialog .swal2-styled,
        .swal2-popup.administrator-account-dialog code {
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.5;
        }
    </style>
@endsection

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
        <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Administrator details">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-panel"
                        type="button" role="tab" aria-controls="profile-panel" aria-selected="true">
                    Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="hrm-tab" data-bs-toggle="tab" data-bs-target="#hrm-panel"
                        type="button" role="tab" aria-controls="hrm-panel" aria-selected="false">
                    HRM
                </button>
            </li>
            @can('manage administrator accounts')
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-panel"
                            type="button" role="tab" aria-controls="account-panel" aria-selected="false">
                        Account
                    </button>
                </li>
            @endcan
        </ul>
        <div class="block-content tab-content">
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
            <div class="tab-pane fade" id="hrm-panel" role="tabpanel" aria-labelledby="hrm-tab">
                @include('hrm.partials.staff-profile', [
                    'employee' => $administrator,
                    'staffStatus' => null,
                ])
            </div>
            @can('manage administrator accounts')
                <div class="tab-pane fade" id="account-panel" role="tabpanel" aria-labelledby="account-tab">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-body p-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <h2 class="fs-6 fw-semibold mb-1">Administrator account</h2>
                                            <p class="text-muted small mb-0">Login details and account status.</p>
                                        </div>
                                        @if ($administrator->User)
                                            <span class="badge {{ $administrator->User->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $administrator->User->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($administrator->User)
                                        <dl class="row g-2 mb-0">
                                            <dt class="col-sm-4 text-muted fw-normal">Login email</dt>
                                            <dd class="col-sm-8 mb-1">{{ $administrator->User->email ?? 'Not set' }}</dd>

                                            <dt class="col-sm-4 text-muted fw-normal">Email verification</dt>
                                            <dd class="col-sm-8 mb-0">
                                                {{ $administrator->User->email_verified_at ? 'Verified' : 'Not verified' }}
                                            </dd>
                                        </dl>

                                        <hr class="my-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('administrators.account.password-reset-link', $administrator) }}" @submit.prevent="submitAdministratorAccountAction($event, 'reset-link')">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" type="submit" :disabled="administratorAccountAction.loading">
                                                    <span v-if="administratorAccountAction.loading && administratorAccountAction.action === 'reset-link'">Sending...</span>
                                                    <span v-else>Send password reset link</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('administrators.account.temporary-password', $administrator) }}" @submit.prevent="submitAdministratorAccountAction($event, 'temporary-password')">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" type="submit" :disabled="administratorAccountAction.loading">
                                                    <span v-if="administratorAccountAction.loading && administratorAccountAction.action === 'temporary-password'">Generating...</span>
                                                    <span v-else>Generate temporary password</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="text-muted small mb-0">No administrator account is linked.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
        </div>
    </div>
    @can('manage administrator accounts')
    <script>
        createApp({
            setup() {
                const administratorAccountAction = ref({ loading: false, action: null });
                const submitAdministratorAccountAction = async (event, action) => {
                    const form = event.currentTarget;
                    administratorAccountAction.value.loading = true;
                    administratorAccountAction.value.action = action;

                    try {
                        const response = await axios.post(form.action, new FormData(form), {
                            headers: { Accept: 'application/json' },
                        });

                        if (response.data.temporary_password) {
                            const passwordDialog = document.createElement('div');
                            passwordDialog.className = 'text-start';

                            const passwordLabel = document.createElement('p');
                            passwordLabel.className = 'mb-2';
                            passwordLabel.textContent = 'Temporary password (shown once):';

                            const passwordValue = document.createElement('code');
                            passwordValue.className = 'd-block p-2 bg-light rounded text-break';
                            passwordValue.textContent = response.data.temporary_password;

                            const instructions = document.createElement('p');
                            instructions.className = 'small mt-3 mb-0';
                            instructions.textContent = response.data.message;

                            passwordDialog.append(passwordLabel, passwordValue, instructions);
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Temporary password generated',
                                html: passwordDialog,
                                confirmButtonText: 'Close',
                                customClass: { popup: 'administrator-account-dialog' },
                            });
                        } else {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Password reset link sent',
                                text: response.data.message,
                                confirmButtonText: 'OK',
                                customClass: { popup: 'administrator-account-dialog' },
                            });
                        }
                    } catch (error) {
                        const response = error.response;
                        const status = response ? response.status : 0;
                        let message = 'The request could not be completed. Please try again.';

                        if ((status === 422 || status === 429 || status === 502) && response.data && response.data.message) {
                            message = response.data.message;
                        } else if (status === 403) {
                            message = 'You are not authorized to manage this administrator account.';
                        } else if (status === 0) {
                            message = 'Network error. Check your connection and try again.';
                        }

                        await Swal.fire({
                            icon: 'error',
                            title: 'Unable to complete request',
                            text: message,
                            confirmButtonText: 'Close',
                            customClass: { popup: 'administrator-account-dialog' },
                        });
                    } finally {
                        administratorAccountAction.value.loading = false;
                        administratorAccountAction.value.action = null;
                    }
                };
                return { administratorAccountAction, submitAdministratorAccountAction };
            }
        }).mount('#administrator');
    </script>
    @endcan
@endsection
