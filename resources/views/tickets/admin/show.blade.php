@extends('layouts.backend')

@section('content')
    @php
        $canManageTickets = auth()->user()->can('manage student tickets');
        $canAssignTickets = auth()->user()->can('assign student tickets') || $canManageTickets;
        $canReplyToTickets = auth()->user()->can('reply to student tickets') || $canManageTickets;
        $canCloseTickets = auth()->user()->can('close student tickets') || $canManageTickets;
        $canReopenTickets = auth()->user()->can('reopen student tickets') || $canManageTickets;
        $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
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
            <a class="fs-sm text-muted" href="{{ route('admin.tickets.index') }}">
                <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> All student tickets
            </a>
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mt-2">
                <div>
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">{{ $ticket->subject }}</h1>
                    <div class="text-muted">
                        Ticket #{{ strtoupper(substr($ticket->id, 0, 8)) }} &middot;
                        {{ $ticket->student?->fname }} {{ $ticket->student?->sname }} &middot;
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

        @if ($canManageTickets || $canAssignTickets || $canCloseTickets || $canReopenTickets)
            <div id="ticket-management" class="block block-rounded block-bordered mb-4">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Ticket management</h3>
                </div>
                <div class="block-content block-content-full">
                    @if ($canManageTickets || $canAssignTickets)
                        <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            @if ($canManageTickets)
                                <div class="col-md-4">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="status" value="{{ $ticket->status }}">
                            @endif
                            @if ($canAssignTickets)
                                <div class="col-md-5">
                                    <label class="form-label" for="assigned_to">Assign to</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="" @selected(old('assigned_to', $ticket->assigned_to) === null)>Unassigned</option>
                                        @foreach ($admins as $admin)
                                            <option value="{{ $admin->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $admin->id)>{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <button class="btn btn-alt-primary rounded-pill px-4 w-100" type="submit">Save changes</button>
                            </div>
                        </form>
                    @endif

                    @if (($isClosed && $canReopenTickets) || (!$isClosed && $canCloseTickets))
                        <div class="d-flex gap-2 mt-3">
                            @if ($isClosed && $canReopenTickets)
                                <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="open">
                                    <button class="btn btn-alt-success rounded-pill px-4" type="submit">Re-open ticket</button>
                                </form>
                            @elseif (!$isClosed && $canCloseTickets)
                                <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="closed">
                                    <button class="btn btn-alt-danger rounded-pill px-4" type="submit">Close ticket</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="mb-4">
            @include('tickets.partials.messages')
        </div>

        @if ($canReplyToTickets)
            <div id="ticket-reply" class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Reply to student</h3>
                </div>
                <div class="block-content block-content-full">
                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" enctype="multipart/form-data">
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
        @endif
    </div>
@endsection
