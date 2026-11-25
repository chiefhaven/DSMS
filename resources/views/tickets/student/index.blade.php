@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">My tickets</h1>
                </div>
                @can('create student tickets')
                    <a class="btn btn-primary rounded-pill px-4" href="{{ route('student.tickets.create') }}">
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
                <h3 class="block-title">Your tickets</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Last updated</th>
                            <th class="text-end"> </th>
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
                                <td class="text-muted">#{{ strtoupper(substr($ticket->id, 0, 8)) }}</td>
                                <td class="fw-semibold">{{ $ticket->subject }}</td>
                                <td>{{ \App\Models\StudentTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ \App\Models\StudentTicket::STATUSES[$ticket->status] ?? $ticket->status }}</span></td>
                                <td>{{ $ticket->updated_at?->format('j M Y, H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-alt-primary rounded-pill px-3" href="{{ route('student.tickets.show', $ticket) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    You have not submitted any tickets yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="block-content block-content-full bg-body-light">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
