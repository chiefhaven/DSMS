@extends('layouts.backend')

@section('content')
@php
    $employee = $employee ?? $instructor ?? null;
    $fullName = trim(($employee->fname ?? '') . ' ' . ($employee->mname ?? '') . ' ' . ($employee->sname ?? ''));
    $status = $employee->status ?? 'Active';
    $statusClass = match (strtolower((string) $status)) {
        'active' => 'bg-success',
        'on leave', 'suspended' => 'bg-warning',
        'terminated', 'resigned', 'contract ended', 'inactive' => 'bg-danger',
        default => 'bg-secondary',
    };
    $email = $employee->email ?? $employee->user?->email ?? $employee->User?->email ?? '';
    $leaveRequests = $leaveRequests ?? collect();
    $leaveTypes = $leaveTypes ?? collect();
    $activeTab = session('hrm_active_tab', 'hrm');
    $canRequestLeave = $canRequestLeave ?? ($isInstructorEmployee ?? (bool) ($employee instanceof \App\Models\Instructor));
@endphp
<div class="content content-full">
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

    <div class="block block-rounded mb-4">
        <div class="block-content p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="fs-3 fw-bold mb-1">My HRM</h1>
                    <p class="text-muted mb-0">{{ $fullName }}@if($email) &middot; {{ $email }}@endif</p>
                </div>
                <div>
                    <span class="badge {{ $statusClass }}">{{ $status }}</span>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-block mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'hrm' ? 'active' : '' }}" id="my-hrm-tab" data-bs-toggle="tab" data-bs-target="#my-hrm-panel" type="button" role="tab">
                <i class="fa fa-briefcase me-2"></i>Employment &amp; Payroll
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'leave' ? 'active' : '' }}" id="my-leave-tab" data-bs-toggle="tab" data-bs-target="#my-leave-panel" type="button" role="tab">
                <i class="fa fa-calendar-days me-2"></i>Leave
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $activeTab === 'hrm' ? 'show active' : '' }}" id="my-hrm-panel" role="tabpanel">
            @include('hrm.partials.staff-profile', ['employee' => $employee, 'staffStatus' => $status])
        </div>

        <div class="tab-pane fade {{ $activeTab === 'leave' ? 'show active' : '' }}" id="my-leave-panel" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="block block-rounded h-100">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Request Leave</h3>
                        </div>
                        <div class="block-content">
                            @if(!$canRequestLeave)
                                <div class="alert alert-info mb-0">Leave requests are currently available for instructor profiles only. Please contact HR for assistance.</div>
                            @elseif(!\Illuminate\Support\Facades\Schema::hasTable('leave_requests'))
                                <div class="alert alert-warning mb-0">Leave requests are not available yet. Please contact an administrator.</div>
                            @else
                                <form method="POST" action="{{ route('instructor.leave-requests.store') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label" for="leave_type_id">Leave type</label>
                                        <select class="form-select" id="leave_type_id" name="leave_type_id">
                                            <option value="">Select leave type (optional)</option>
                                            @foreach($leaveTypes->where('is_active', true) as $type)
                                                <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6 mb-3">
                                            <label class="form-label" for="leave_start_date">Start date</label>
                                            <input type="date" class="form-control" id="leave_start_date" name="start_date" value="{{ old('start_date') }}" required>
                                        </div>
                                        <div class="col-sm-6 mb-3">
                                            <label class="form-label" for="leave_end_date">End date</label>
                                            <input type="date" class="form-control" id="leave_end_date" name="end_date" value="{{ old('end_date') }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="leave_reason">Reason</label>
                                        <textarea class="form-control" id="leave_reason" name="reason" rows="4" maxlength="2000" placeholder="Reason for leave (optional)">{{ old('reason') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-paper-plane me-1"></i> Submit request
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="block block-rounded h-100">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">My Leave Requests</h3>
                        </div>
                        <div class="block-content block-content-full">
                            @if($leaveRequests->isEmpty())
                                <p class="text-muted mb-0">No leave requests found.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter fs-sm mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Type</th>
                                                <th>Dates</th>
                                                <th class="text-center">Days</th>
                                                <th>Status</th>
                                                <th>Reviewed by</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($leaveRequests as $leave)
                                                @php
                                                    $badge = match($leave->status) {
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'cancelled' => 'bg-secondary',
                                                        default => 'bg-warning',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td>{{ $leave->leaveType?->name ?? '—' }}</td>
                                                    <td>
                                                        {{ $leave->start_date?->format('d-m-Y') }} to {{ $leave->end_date?->format('d-m-Y') }}
                                                        @if($leave->reason)
                                                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($leave->reason, 80) }}</div>
                                                        @endif
                                                        @if($leave->review_notes)
                                                            <div class="text-muted small mt-1"><strong>Review:</strong> {{ \Illuminate\Support\Str::limit($leave->review_notes, 80) }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $leave->days }}</td>
                                                    <td><span class="badge {{ $badge }}">{{ ucfirst($leave->status) }}</span></td>
                                                    <td>{{ $leave->reviewer?->name ?? ($leave->status === 'pending' ? 'Pending review' : '—') }}</td>
                                                    <td class="text-end">
                                                        @if($leave->status === 'pending')
                                                            <form method="POST" action="{{ route('instructor.leave-requests.cancel', $leave->id) }}" onsubmit="return confirm('Cancel this leave request?');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-alt-secondary">Cancel</button>
                                                            </form>
                                                        @else
                                                            <span class="text-muted">—</span>
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
            </div>
        </div>
    </div>
</div>
@endsection
