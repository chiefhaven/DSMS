@props(['cancelUrl' => null, 'modal' => false, 'cancelClick' => null])

<div {{ $attributes->class(['form-actions d-flex flex-wrap justify-content-end align-items-center gap-2 border-top bg-body-light rounded-3 p-3', 'mt-4' => !$modal, 'modal-footer' => $modal]) }}>
    @if (isset($cancel))
        {{ $cancel }}
    @elseif ($cancelUrl)
        <a href="{{ $cancelUrl }}" class="btn btn-alt-secondary rounded-pill px-4">Cancel</a>
    @else
        <button type="button" class="btn btn-alt-secondary rounded-pill px-4" @if ($cancelClick) @click="{{ $cancelClick }}" @elseif ($modal) data-bs-dismiss="modal" @else onclick="this.closest('form').reset()" @endif>Cancel</button>
    @endif
    @if ($slot->isEmpty())
        <button type="submit" class="btn btn-primary rounded-pill px-4">Save</button>
    @else
        {{ $slot }}
    @endif
</div>
