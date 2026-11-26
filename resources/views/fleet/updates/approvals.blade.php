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
                                    <div>Odometer reading date: {{ $submission->fleet->odometer_reading_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>Last service: {{ $submission->fleet->service_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>COF: {{ $submission->fleet->roadworthiness_expiry?->format('Y-m-d') ?? '—' }}</div>
                                    <div>Insurance: {{ $submission->fleet->insurance_expiry?->format('Y-m-d') ?? '—' }}</div>
                                </td>
                                <td class="fs-sm">
                                    <div>Odometer: {{ number_format($submission->odometer_km) }} km</div>
                                    <div>Odometer reading date: {{ $submission->odometer_reading_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>Last service: {{ $submission->service_date?->format('Y-m-d') ?? '—' }}</div>
                                    <div>COF: {{ $submission->roadworthiness_expiry->format('Y-m-d') }}</div>
                                    <div>Insurance: {{ $submission->insurance_expiry->format('Y-m-d') }}</div>
                                </td>
                                <td class="fs-sm">
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        @if ($submission->odometer_picture_path)
                                            <a class="btn btn-link p-0 text-start"
                                               href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'odometer']) }}"
                                               data-bs-toggle="modal" data-bs-target="#fleet-evidence-modal"
                                               data-fleet-evidence-title="Odometer photo"
                                               data-fleet-evidence-url="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'odometer']) }}">Odometer photo</a>
                                        @endif
                                        @if ($submission->service_date_picture_path)
                                            <a class="btn btn-link p-0 text-start"
                                               href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'service-date']) }}"
                                               data-bs-toggle="modal" data-bs-target="#fleet-evidence-modal"
                                               data-fleet-evidence-title="Service record photo"
                                               data-fleet-evidence-url="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'service-date']) }}">Service record photo</a>
                                        @endif
                                        @if ($submission->roadworthiness_picture_path)
                                            <a class="btn btn-link p-0 text-start"
                                               href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'cof-due']) }}"
                                               data-bs-toggle="modal" data-bs-target="#fleet-evidence-modal"
                                               data-fleet-evidence-title="COF photo"
                                               data-fleet-evidence-url="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'cof-due']) }}">COF photo</a>
                                        @endif
                                        @if ($submission->insurance_picture_path)
                                            <a class="btn btn-link p-0 text-start"
                                               href="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'insurance-due']) }}"
                                               data-bs-toggle="modal" data-bs-target="#fleet-evidence-modal"
                                               data-fleet-evidence-title="Insurance photo"
                                               data-fleet-evidence-url="{{ route('fleet-update-approvals.evidence', ['submission' => $submission, 'field' => 'insurance-due']) }}">Insurance photo</a>
                                        @endif
                                        @unless ($submission->odometer_picture_path || $submission->service_date_picture_path || $submission->roadworthiness_picture_path || $submission->insurance_picture_path)
                                            <span class="text-muted">—</span>
                                        @endunless
                                    </div>
                                </td>
                                <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-alt-secondary btn-sm dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                                aria-expanded="false"
                                                aria-label="Actions for {{ $submission->fleet->car_registration_number }}">
                                            Action
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <form method="POST" action="{{ route('fleet-update-approvals.approve', $submission) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="fa fa-check me-2" aria-hidden="true"></i>Approve
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('fleet-update-approvals.reject', $submission) }}">
                                                    @csrf
                                                    <div class="px-3 py-2">
                                                        <label class="form-label fs-xs mb-1" for="review-note-{{ $submission->getKey() }}">Optional rejection reason</label>
                                                        <input class="form-control form-control-sm"
                                                               id="review-note-{{ $submission->getKey() }}"
                                                               name="review_note" type="text" maxlength="2000">
                                                    </div>
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa fa-times me-2" aria-hidden="true"></i>Reject
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
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

        <div class="modal fade" id="fleet-evidence-modal" tabindex="-1"
             aria-labelledby="fleet-evidence-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary">
                        <h2 class="modal-title fs-5 text-white" id="fleet-evidence-modal-title">Evidence image</h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img class="img-fluid rounded" data-fleet-evidence-image alt="" style="max-height: 75vh;">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const fleetEvidenceModal = document.getElementById('fleet-evidence-modal');

        fleetEvidenceModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            const image = fleetEvidenceModal.querySelector('[data-fleet-evidence-image]');
            const title = fleetEvidenceModal.querySelector('#fleet-evidence-modal-title');

            image.src = trigger.dataset.fleetEvidenceUrl;
            image.alt = trigger.dataset.fleetEvidenceTitle;
            title.textContent = trigger.dataset.fleetEvidenceTitle;
        });

        fleetEvidenceModal.addEventListener('hidden.bs.modal', () => {
            fleetEvidenceModal.querySelector('[data-fleet-evidence-image]').removeAttribute('src');
        });
    </script>
@endpush
