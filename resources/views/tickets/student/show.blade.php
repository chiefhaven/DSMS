@extends('layouts.backend')

@section('content')
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

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <a class="fs-sm text-muted" href="{{ route('student.tickets.index') }}">
                        <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> My support tickets
                    </a>
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">{{ $ticket->subject }}</h1>
                    <div class="text-muted">
                        Ticket #{{ strtoupper(substr($ticket->id, 0, 8)) }} &middot;
                        {{ \App\Models\StudentTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}
                    </div>
                </div>
                <span class="badge bg-{{ $statusClass }} fs-sm px-3 py-2">
                    {{ \App\Models\StudentTicket::STATUSES[$ticket->status] ?? $ticket->status }}
                </span>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="mb-4">
            @include('tickets.partials.messages')
        </div>

        @if (in_array($ticket->status, ['resolved', 'closed'], true) && auth()->user()->can('reply to student tickets'))
            <div class="alert alert-info" role="status">Replying will reopen this ticket.</div>
        @endif

        @can('reply to student tickets')
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Reply to this ticket</h3>
                </div>
                <div class="block-content block-content-full">
                    <form method="POST" action="{{ route('student.tickets.reply', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="body">Message</label>
                            <textarea class="form-control" id="body" name="body" rows="5" maxlength="5000" required>{{ old('body') }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="attachments">Attachments <span class="text-muted">(optional)</span></label>
                            <input class="form-control" id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <div class="form-text">Up to 5 files, 10 MB each. PDF, JPG, PNG, DOC, or DOCX.</div>
                        </div>
                        <button class="btn btn-alt-primary rounded-pill px-4" type="submit">
                            <i class="fa fa-paper-plane me-1" aria-hidden="true"></i> Send reply
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection
