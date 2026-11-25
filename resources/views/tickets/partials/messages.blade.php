<div>
    @forelse ($ticket->messages as $message)
        <article class="block block-rounded block-bordered mb-3">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    {{ $message->user?->name ?? 'Former account' }}
                    @if ($message->user_id === auth()->id())
                        <span class="badge bg-primary ms-1">You</span>
                    @endif
                </h3>
                <div class="block-options">
                    <time class="text-muted fs-sm" datetime="{{ $message->created_at?->toIso8601String() }}">
                    {{ $message->created_at?->format('j M Y, H:i') }}
                    </time>
                </div>
            </div>
            <div class="block-content">
                <div class="text-break">{!! nl2br(e($message->body)) !!}</div>

                @if ($message->attachments->isNotEmpty())
                    <div class="border-top mt-3 pt-3">
                        <div class="small text-muted fw-semibold mb-2">Attachments</div>
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
            </div>
        </article>
    @empty
        <div class="text-muted">No messages yet.</div>
    @endforelse
</div>
