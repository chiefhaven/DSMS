@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'leaves'])

    <div class="content content-full">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <h2 class="fs-4 fw-semibold mb-0">Leaves</h2>
        </div>

        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Leave management">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="leave-type-tab" data-bs-toggle="tab" data-bs-target="#leave-type-panel"
                            type="button" role="tab" aria-controls="leave-type-panel" aria-selected="true">
                        <i class="fa fa-list-check me-2"></i>Leave Type
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="leave-tab" data-bs-toggle="tab" data-bs-target="#leave-panel"
                            type="button" role="tab" aria-controls="leave-panel" aria-selected="false">
                        <i class="fa fa-calendar-days me-2"></i>Leave
                    </button>
                </li>
            </ul>
            <div class="block-content tab-content">
                <div class="tab-pane fade show active py-5 text-center" id="leave-type-panel" role="tabpanel" aria-labelledby="leave-type-tab">
                    <i class="fa fa-list-check fs-2 text-muted mb-3" aria-hidden="true"></i>
                    <h3 class="fs-5 fw-semibold">Leave Type</h3>
                    <p class="text-muted mb-0">Leave type management is coming soon.</p>
                </div>
                <div class="tab-pane fade py-5 text-center" id="leave-panel" role="tabpanel" aria-labelledby="leave-tab">
                    <i class="fa fa-calendar-days fs-2 text-muted mb-3" aria-hidden="true"></i>
                    <h3 class="fs-5 fw-semibold">Leave</h3>
                    <p class="text-muted mb-0">Leave management is coming soon.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
