<section class="block block-rounded block-bordered mb-4" aria-labelledby="ticket-conversation-title">
    <div class="block-header block-header-default">
        <h2 class="block-title" id="ticket-conversation-title">
            <i class="fa fa-comments text-primary me-2" aria-hidden="true"></i>Conversation
        </h2>
        <div class="block-options">
            <span class="badge rounded-pill bg-body-light text-muted">{{ $ticket->messages->count() }}</span>
        </div>
    </div>
    <div class="block-content block-content-full">
        @forelse ($ticket->messages as $message)
            <article class="d-flex align-items-start gap-3 {{ !$loop->last ? 'border-bottom pb-4 mb-4' : '' }}">
                <span class="item item-rounded bg-primary-light text-primary fw-semibold flex-shrink-0" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($message->user?->name ?? 'Former account', 0, 1)) }}
                </span>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-start gap-1 mb-2">
                        <div class="fw-semibold">
                            {{ $message->user?->name ?? 'Former account' }}
                            @if ($message->user_id === auth()->id())
                                <span class="badge rounded-pill bg-primary-light text-primary ms-1">You</span>
                            @endif
                        </div>
                        <time class="text-muted fs-xs text-nowrap" datetime="{{ $message->created_at?->toIso8601String() }}">
                            {{ $message->created_at?->format('j M Y, H:i') }}
                        </time>
                    </div>
                    <div class="text-break fs-sm lh-lg">{!! nl2br(e($message->body)) !!}</div>

                    @if ($message->response_time_label ?? false)
                        <div class="fs-xs text-muted mt-2">
                            <i class="fa fa-stopwatch me-1" aria-hidden="true"></i>
                            Response time: {{ $message->response_time_label }}
                        </div>
                    @endif

                    @if ($message->attachments->isNotEmpty())
                        <div class="border-top mt-3 pt-3">
                            <div class="fs-xs text-muted fw-semibold text-uppercase mb-2">Attachments</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($message->attachments as $attachment)
                                    <a class="btn btn-sm btn-alt-secondary rounded-pill px-3"
                                       href="{{ route('tickets.attachments.download', $attachment) }}">
                                        <i class="fa fa-paperclip me-1" aria-hidden="true"></i>
                                        {{ $attachment->original_name }}
                                        <span class="text-muted ms-1">
                                            ({{ $attachment->size >= 1048576
                                                ? number_format($attachment->size / 1048576, 1) . ' MB'
                                                : number_format($attachment->size / 1024) . ' KB' }})
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->hasRole('student') && $message->user && !$message->user->hasRole('student'))
                        @php
                            $currentVote = $message->votes->firstWhere('student_id', auth()->user()->student_id);
                        @endphp
                        <form class="js-ticket-response-rating ticket-response-rating border-top mt-3 pt-3 d-flex flex-wrap align-items-center gap-2"
                              method="POST"
                              action="{{ route('student.tickets.messages.vote', [$ticket, $message]) }}"
                              data-saved-rating="{{ $currentVote?->rating ?? '' }}">
                            @csrf
                            <fieldset class="d-flex align-items-center gap-0 mb-0">
                                <legend class="visually-hidden">Rate this response from 1 to 5 stars</legend>
                                @for ($rating = 1; $rating <= 5; $rating++)
                                    <input class="btn-check"
                                           type="radio"
                                           name="rating"
                                           id="rating-{{ $message->id }}-{{ $rating }}"
                                           value="{{ $rating }}"
                                           autocomplete="off"
                                           @checked((int) old('rating', $currentVote?->rating) === $rating)
                                           required>
                                    <label class="ticket-response-rating-star {{ $rating <= (int) old('rating', $currentVote?->rating) ? 'is-selected' : '' }}"
                                           for="rating-{{ $message->id }}-{{ $rating }}"
                                           title="{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}">
                                        <i class="fa fa-star" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}</span>
                                    </label>
                                @endfor
                            </fieldset>
                            <span class="fs-xs text-muted"
                                  data-rating-status
                                  aria-live="polite">{{ $currentVote ? 'Your rating: ' . $currentVote->rating . '/5' : 'Click a star to rate this response.' }}</span>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="text-center text-muted py-4">
                <i class="fa fa-comments fa-2x text-primary mb-3" aria-hidden="true"></i>
                <div class="fw-semibold">No messages yet</div>
            </div>
        @endforelse
    </div>
</section>
