@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <h1 class="fs-3 fw-semibold my-2 my-sm-3">
                Fleet data update approvals
            </h1>
            <p class="text-muted mb-0">Review practical instructor submissions before applying them to fleet records.</p>
        </div>
    </div>

    <div class="content content-full">
        @if (session('message'))
            <div class="alert alert-success" role="status">{{ session('message') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h2 class="block-title">Pending fleet updates</h2>
            </div>
            <div class="block-content block-content-full fs-sm p-0">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-vcenter align-middle mb-0">
                    <thead class="bg-body-light">
                        <tr class="text-uppercase fs-xs text-muted">
                            <th>Vehicle</th>
                            <th>Submitted by</th>
                            <th>Current data</th>
                            <th>Proposed data</th>
                            <th>Evidence</th>
                            <th>Submitted</th>
                            <th class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvalRequests as $submission)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $submission->fleet->car_brand_model }}</div>
                                    <div class="fs-sm text-muted">{{ $submission->fleet->car_registration_number }}</div>
                                </td>
                                <td>{{ $submission->submitter?->name ?? 'Account removed' }}</td>
                                <td class="fs-sm">
                                    <div>Odometer: {{ $submission->fleet->odometer_km === null ? '—' : number_format($submission->fleet->odometer_km).' km' }}</div>
                                    <div>Last service: {{ $submission->fleet->service_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>COF: {{ $submission->fleet->roadworthiness_expiry?->format('Y-m-d') ?? '—' }}</div>
                                    <div>Insurance: {{ $submission->fleet->insurance_expiry?->format('Y-m-d') ?? '—' }}</div>
                                </td>
                                <td class="fs-sm">
                                    <div>Odometer: {{ number_format($submission->odometer_km) }} km</div>
                                    <div>Last service: {{ $submission->service_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>COF: {{ $submission->roadworthiness_expiry->format('Y-m-d') }}</div>
                                    <div>Insurance: {{ $submission->insurance_expiry->format('Y-m-d') }}</div>
                                </td>
                                <td class="fs-sm">
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        @if ($submission->odometer_picture_path)
                                            <a href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'odometer']) }}" target="_blank" rel="noopener noreferrer">Odometer photo</a>
                                        @endif
                                        @if ($submission->service_date_picture_path)
                                            <a href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'service-date']) }}" target="_blank" rel="noopener noreferrer">Service record photo</a>
                                        @endif
                                        @if ($submission->roadworthiness_picture_path)
                                            <a href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'cof-due']) }}" target="_blank" rel="noopener noreferrer">COF photo</a>
                                        @endif
                                        @if ($submission->insurance_picture_path)
                                            <a href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'insurance-due']) }}" target="_blank" rel="noopener noreferrer">Insurance photo</a>
                                        @endif
                                        @unless ($submission->odometer_picture_path || $submission->service_date_picture_path || $submission->roadworthiness_picture_path || $submission->insurance_picture_path)
                                            <span class="text-muted">—</span>
                                        @endunless
                                    </div>
                                </td>
                                <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-column gap-2">
                                        <form method="POST" action="{{ route('fleet-update-approvals.approve', $submission) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fa fa-check me-1" aria-hidden="true"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('fleet-update-approvals.reject', $submission) }}">
                                            @csrf
                                            <input class="form-control form-control-sm mb-1" name="review_note" type="text" maxlength="2000" placeholder="Optional reason">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                                <i class="fa fa-times me-1" aria-hidden="true"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">There are no pending fleet updates.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            @if ($approvalRequests->hasPages())
                <div class="block-content block-content-full border-top">{{ $approvalRequests->links() }}</div>
            @endif
        </div>
    </div>
@endsection
