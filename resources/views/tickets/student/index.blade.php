@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">
                        <i class="fa fa-life-ring text-primary me-2" aria-hidden="true"></i>My support tickets
                    </h1>
                    <p class="fs-sm text-muted mb-0">Track updates and continue your conversations with support.</p>
                </div>
                @can('create student tickets')
                    <a class="btn btn-alt-primary rounded-pill px-4" href="{{ route('student.tickets.create') }}">
                        <i class="fa fa-plus me-1" aria-hidden="true"></i> New ticket
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h2 class="block-title">
                    <i class="fa fa-inbox text-primary me-2" aria-hidden="true"></i>Your tickets
                </h2>
                <div class="block-options">
                    <span class="badge rounded-pill bg-body-light text-muted">{{ $tickets->total() }}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter align-middle mb-0 fs-sm">
                    <thead class="bg-body-light">
                        <tr>
                            <th class="fs-xs text-uppercase text-muted" scope="col">Ticket</th>
                            <th class="fs-xs text-uppercase text-muted" scope="col">Subject</th>
                            <th class="fs-xs text-uppercase text-muted" scope="col">Category</th>
                            <th class="fs-xs text-uppercase text-muted" scope="col">Status</th>
                            <th class="fs-xs text-uppercase text-muted" scope="col">Last updated</th>
                            <th class="text-end" scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            @php
                                $statusClass = match ($ticket->status) {
                                    'open' => 'primary',
                                    'in_progress' => 'warning',
                                    'waiting_on_student' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td class="text-muted font-monospace">#{{ strtoupper(substr($ticket->id, 0, 8)) }}</td>
                                <td class="fw-semibold">{{ $ticket->subject }}</td>
                                <td>{{ \App\Models\StudentTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}</td>
                                <td><span class="badge rounded-pill bg-{{ $statusClass }} px-2">{{ \App\Models\StudentTicket::STATUSES[$ticket->status] ?? $ticket->status }}</span></td>
                                <td class="text-muted">{{ $ticket->updated_at?->format('j M Y, H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-alt-primary rounded-pill px-3" href="{{ route('student.tickets.show', $ticket) }}">
                                        View <i class="fa fa-angle-right ms-1" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa fa-inbox fa-2x text-primary d-block mb-3" aria-hidden="true"></i>
                                    <span class="fw-semibold d-block mb-1">No support tickets yet</span>
                                    <span class="fs-sm">When you need help, submit a ticket and your conversation will appear here.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="block-content block-content-full border-top bg-body-light">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
