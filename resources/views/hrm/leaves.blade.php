@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'leaves'])

    @php
        $leaveTypes = $leaveTypes ?? collect();
        $leaveRequests = $leaveRequests ?? collect();
        $tablesReady = \Illuminate\Support\Facades\Schema::hasTable('leave_types') && \Illuminate\Support\Facades\Schema::hasTable('leave_requests');
    @endphp

    <div class="content content-full">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h2 class="fs-4 fw-semibold mb-0">Leaves</h2>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @unless($tablesReady)
            <div class="alert alert-warning">Leave tables are not available yet. Run migrations to enable leave management.</div>
        @endunless

        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Leave management">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Leave management">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="leave-tab" data-bs-toggle="tab" data-bs-target="#leave-panel"
                            type="button" role="tab" aria-controls="leave-panel" aria-selected="true">
                        <i class="fa fa-calendar-days me-2"></i>Leave Requests
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="leave-type-tab" data-bs-toggle="tab" data-bs-target="#leave-type-panel"
                            type="button" role="tab" aria-controls="leave-type-panel" aria-selected="false">
                        <i class="fa fa-list-check me-2"></i>Leave Types
                    </button>
                </li>
            </ul>
            <div class="block-content tab-content">
                <div class="tab-pane fade show active" id="leave-panel" role="tabpanel" aria-labelledby="leave-tab">
                    <div class="py-2">
                        <div class="card-body p-0">
                            @if($leaveRequests->isEmpty())
                                <div class="p-3 text-muted">No leave requests found.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter mb-0 fs-sm align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Employee</th>
                                                <th>Type</th>
                                                <th>Dates</th>
                                                <th class="text-center">Days</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Reviewed by</th>
                                                <th style="min-width: 260px;">Review action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($leaveRequests as $leave)
                                                @php
                                                    $employeeName = $leave->employee_name ?? trim(($leave->instructor->fname ?? $leave->administrator->fname ?? '') . ' ' . ($leave->instructor->mname ?? $leave->administrator->mname ?? '') . ' ' . ($leave->instructor->sname ?? $leave->administrator->sname ?? '')) ?: 'Unknown';
                                                    $employeeType = $leave->employee_type ?? ($leave->administrator_id ? 'Administrator' : 'Instructor');
                                                    $badge = match($leave->status) {
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'cancelled' => 'bg-secondary',
                                                        default => 'bg-warning',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td class="fw-semibold">
                                                        {{ $employeeName }}
                                                        <div class="text-muted small">{{ $employeeType }}</div>
                                                    </td>
                                                    <td>{{ $leave->leaveType?->name ?? '—' }}</td>
                                                    <td>{{ $leave->start_date?->format('d-m-Y') }} to {{ $leave->end_date?->format('d-m-Y') }}</td>
                                                    <td class="text-center">{{ $leave->days }}</td>
                                                    <td>{{ $leave->reason ? \Illuminate\Support\Str::limit($leave->reason, 60) : '—' }}</td>
                                                    <td><span class="badge {{ $badge }}">{{ ucfirst($leave->status) }}</span></td>
                                                    <td>
                                                        {{ $leave->reviewer?->name ?? '—' }}
                                                        @if($leave->reviewed_at)
                                                            <div class="text-muted small">{{ $leave->reviewed_at->format('d-m-Y H:i') }}</div>
                                                        @endif
                                                        @if($leave->review_notes)
                                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($leave->review_notes, 60) }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($leave->status === 'pending')
                                                            <form method="POST" action="{{ route('leaves.review', $leave->id) }}" class="d-flex flex-column gap-1">
                                                                @csrf
                                                                <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Review notes (optional)" maxlength="2000">
                                                                <div class="d-flex gap-1">
                                                                    <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">Approve</button>
                                                                    <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                                                </div>
                                                            </form>
                                                        @else
                                                            <span class="text-muted">No action</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-labelledby="leaveTypeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('leaves.types.store') }}" id="leave-type-form">
                                @csrf
                                <input type="hidden" name="_method" id="leave_type_form_method" value="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="leaveTypeModalLabel">Add Leave Type</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="leave_type_name">Name</label>
                                        <input type="text" class="form-control" id="leave_type_name" name="name" required maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="leave_type_description">Description</label>
                                        <textarea class="form-control" id="leave_type_description" name="description" rows="3" maxlength="2000"></textarea>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="leave_type_active" name="is_active" checked>
                                        <label class="form-check-label" for="leave_type_active">Active</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="leave-type-submit-btn">Save leave type</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="leave-type-panel" role="tabpanel" aria-labelledby="leave-type-tab">
                    <div class="py-2">
                        <div class="card-header bg-body-light d-flex justify-content-between align-items-center">
                            <h3 class="fs-6 fw-semibold mb-0"></h3>
                            <button type="button" class="btn btn-sm btn-primary" id="add-leave-type-btn" @unless($tablesReady) disabled @endunless>
                                <i class="fa fa-plus me-1"></i> Add leave type
                            </button>
                        </div>
                        <div class="card-body p-0"> 
                            @if($leaveTypes->isEmpty())
                                <div class="p-3 text-muted">No leave types found.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter mb-0 fs-sm">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th class="text-center" style="min-width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($leaveTypes as $type)
                                                <tr>
                                                    <td class="fw-semibold">{{ $type->name }}</td>
                                                    <td>{{ $type->description ?: '—' }}</td>
                                                    <td>
                                                        <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" type="button" data-bs-toggle="dropdown" aria-expanded="false" @unless($tablesReady) disabled @endunless>
                                                                <i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end">
                                                                <button type="button"
                                                                    class="dropdown-item edit-leave-type-btn"
                                                                    data-id="{{ $type->id }}"
                                                                    data-name="{{ e($type->name) }}"
                                                                    data-description="{{ e($type->description ?? '') }}"
                                                                    data-active="{{ $type->is_active ? '1' : '0' }}"
                                                                    data-update-url="{{ route('leaves.types.update', $type->id) }}">
                                                                    <i class="fa fa-pen-to-square me-1"></i> Edit
                                                                </button>
                                                                <form method="POST" action="{{ route('leaves.types.destroy', $type->id) }}" onsubmit="return confirm('Delete this leave type?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fa fa-trash me-1"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('leaveTypeModal');
            const form = document.getElementById('leave-type-form');
            if (!modalEl || !form || typeof bootstrap === 'undefined') {
                return;
            }

            const modal = new bootstrap.Modal(modalEl);
            const storeUrl = @json(route('leaves.types.store'));
            const titleEl = document.getElementById('leaveTypeModalLabel');
            const methodEl = document.getElementById('leave_type_form_method');
            const nameEl = document.getElementById('leave_type_name');
            const descEl = document.getElementById('leave_type_description');
            const activeEl = document.getElementById('leave_type_active');
            const submitEl = document.getElementById('leave-type-submit-btn');

            function openAdd() {
                form.action = storeUrl;
                methodEl.value = 'POST';
                titleEl.textContent = 'Add Leave Type';
                submitEl.textContent = 'Save leave type';
                nameEl.value = '';
                descEl.value = '';
                activeEl.checked = true;
                modal.show();
            }

            function openEdit(btn) {
                form.action = btn.dataset.updateUrl || storeUrl;
                methodEl.value = 'PUT';
                titleEl.textContent = 'Edit Leave Type';
                submitEl.textContent = 'Update leave type';
                nameEl.value = btn.dataset.name || '';
                descEl.value = btn.dataset.description || '';
                activeEl.checked = btn.dataset.active === '1';
                modal.show();
            }

            const addBtn = document.getElementById('add-leave-type-btn');
            if (addBtn) {
                addBtn.addEventListener('click', openAdd);
            }

            document.querySelectorAll('.edit-leave-type-btn').forEach(function (btn) {
                btn.addEventListener('click', function () { openEdit(btn); });
            });
        });
    </script>
@endsection
