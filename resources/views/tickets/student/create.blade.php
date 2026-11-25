@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <a class="fs-sm text-muted" href="{{ route('student.tickets.index') }}">
                <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Back to my tickets
            </a>
            <h1 class="fs-3 fw-semibold my-2 my-sm-3">Create a support ticket</h1>
        </div>
    </div>

    <div class="content content-full">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">
                <form method="POST" action="{{ route('student.tickets.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="category">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Choose a category</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="subject">Subject</label>
                        <input class="form-control" id="subject" name="subject" type="text" maxlength="200" value="{{ old('subject') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="body">Message</label>
                        <textarea class="form-control" id="body" name="body" rows="6" maxlength="5000" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="attachments">Attachments <span class="text-muted">(optional)</span></label>
                        <input class="form-control" id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">Up to 5 files, 10 MB each. PDF, JPG, PNG, DOC, or DOCX.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary rounded-pill px-4" type="submit">
                            <i class="fa fa-paper-plane me-1" aria-hidden="true"></i> Submit ticket
                        </button>
                        <a class="btn btn-alt-secondary rounded-pill px-4" href="{{ route('student.tickets.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
