@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <a class="fs-sm text-muted" href="{{ route('students.index') }}">
                        <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Back to students
                    </a>
                    <h1 class="fs-3 fw-semibold mt-2 mb-1">TRN approvals</h1>
                </div>
            </div>
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

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h2 class="h5 mb-0">Pending TRN changes</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase fs-xs text-muted">
                            <th>Student</th>
                            <th>Current TRN</th>
                            <th>Requested TRN</th>
                            <th>Picture</th>
                            <th>Submitted by</th>
                            <th>Submitted</th>
                            <th class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvalRequests as $approval)
                            <tr>
                                <td>{{ trim($approval->fname.' '.($approval->mname ?? '').' '.$approval->sname) }}</td>
                                <td>{{ $approval->current_trn ?: '—' }}</td>
                                <td>
                                    @if (is_null($approval->requested_trn))
                                        Clear TRN
                                    @else
                                        {{ $approval->requested_trn }}
                                    @endif
                                </td>
                                <td>
                                    <img src="{{ route('students.trn-approvals.picture', ['approval' => $approval->id]) }}"
                                         alt="Requested TRN picture"
                                         class="img-thumbnail"
                                         style="width: 8rem; height: 6rem; object-fit: contain;">
                                </td>
                                <td>{{ $approval->requester_name ?: $approval->requested_by }}</td>
                                <td>{{ \Carbon\Carbon::parse($approval->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <form method="POST" action="{{ route('students.trn-approvals.approve', ['approval' => $approval->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fa fa-check me-1" aria-hidden="true"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('students.trn-approvals.reject', ['approval' => $approval->id]) }}" onsubmit="return confirm('Reject this TRN change?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                                <i class="fa fa-times me-1" aria-hidden="true"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">There are no pending TRN changes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($approvalRequests->hasPages())
                <div class="card-footer bg-white">{{ $approvalRequests->links() }}</div>
            @endif
        </div>
    </div>
@endsection
