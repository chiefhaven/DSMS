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
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">
                        <i class="fa fa-ticket-alt text-primary me-2" aria-hidden="true"></i>{{ $ticket->subject }}
                    </h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 fs-sm text-muted">
                        <span class="font-monospace">#{{ strtoupper(substr($ticket->id, 0, 8)) }}</span>
                        <span aria-hidden="true">&middot;</span>
                        <span>{{ \App\Models\StudentTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}</span>
                    </div>
                </div>
                <span class="badge rounded-pill bg-{{ $statusClass }} fs-sm px-3 py-2">
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

        @include('tickets.partials.messages')

        @if (in_array($ticket->status, ['resolved', 'closed'], true) && auth()->user()->can('reply to student tickets'))
            <div class="alert alert-info d-flex align-items-center gap-2" role="status">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <span>Replying will reopen this ticket.</span>
            </div>
        @endif

        @can('reply to student tickets')
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h2 class="block-title"><i class="fa fa-reply text-primary me-2" aria-hidden="true"></i>Reply to this ticket</h2>
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

@section('js_after')
    <script>
        document.querySelectorAll('.js-ticket-response-rating').forEach(function (form) {
            form.addEventListener('change', async function (event) {
                const ratingInput = event.target.closest('input[name="rating"]');
                if (!ratingInput) {
                    return;
                }

                const fieldset = form.querySelector('fieldset');
                const status = form.querySelector('[data-rating-status]');
                const previousRating = form.dataset.savedRating;
                const updateStarDisplay = function (rating) {
                    form.querySelectorAll('.ticket-response-rating-star').forEach(function (star, index) {
                        star.classList.toggle('is-selected', index < Number(rating));
                    });
                };

                updateStarDisplay(ratingInput.value);
                fieldset.disabled = true;
                status.textContent = 'Saving rating...';

                try {
                    const response = await axios.post(form.action, {
                        rating: ratingInput.value
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    form.dataset.savedRating = String(response.data.rating);
                    status.textContent = 'Your rating: ' + response.data.rating + '/5';
                } catch (error) {
                    const inputs = form.querySelectorAll('input[name="rating"]');
                    inputs.forEach(function (input) {
                        input.checked = input.value === previousRating;
                    });
                    updateStarDisplay(previousRating);

                    status.textContent = error.response?.data?.message
                        || 'Could not save your rating. Please try again.';
                } finally {
                    fieldset.disabled = false;
                }
            });
        });
    </script>
@endsection
