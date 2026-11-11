@php
    $currentStudent = $editing ? $student : null;
    $cancelUrl = $editing ? route('students.show', ['student' => $student->id]) : route('students.index');
    $fields = [
        'fname' => ['label' => 'First name', 'required' => true, 'autocomplete' => 'given-name'],
        'mname' => ['label' => 'Other names', 'required' => false, 'autocomplete' => 'additional-name'],
        'sname' => ['label' => 'Last name', 'required' => true, 'autocomplete' => 'family-name'],
    ];
@endphp

<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <a class="fs-sm text-muted" href="{{ $cancelUrl }}">
                    <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> {{ $editing ? 'Back to student' : 'Back to students' }}
                </a>
                <h1 class="fs-3 fw-semibold mt-2 mb-1">
                    @if ($editing)
                        Editing <em>{{ $student->fname.($student->mname ? ' '.$student->mname : '').' '.$student->sname }}</em>
                    @else
                        Add student
                    @endif
                </h1>
                @if (!$editing)
                    <p class="text-muted mb-0">Student details and account.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="content content-full">
    @if (session('message'))
        <div class="alert alert-success" role="status">{{ session('message') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-2">Check the highlighted fields.</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="student-form" action="{{ $editing ? route('students.update', ['student' => $student->id]) : route('students.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($editing)
            @method('PUT')
            <input type="hidden" name="student_id" value="{{ $student->id }}">
        @endif
        <div class="row g-4">
            <div class="{{ $editing ? 'col-12' : 'col-xl-8' }}">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h2 class="block-title"><i class="fa fa-user text-muted me-2" aria-hidden="true"></i>Personal information</h2>
                    </div>
                    <div class="block-content block-content-full">
                        <p class="text-muted fs-sm mb-4"><span class="text-danger">*</span> Required.</p>
                        <div class="row g-4">
                            @foreach ($fields as $name => $field)
                                <div class="col-md-4">
                                    <label class="form-label" for="{{ $name }}">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-danger">*</span>
                                        @else
                                            <span class="text-muted fw-normal fs-sm">(optional)</span>
                                        @endif
                                    </label>
                                    <input type="text" class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $currentStudent?->{$name}) }}" autocomplete="{{ $field['autocomplete'] }}" @required($field['required']) @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                                    @error($name)
                                        <div id="{{ $name }}-error" class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                            <div class="col-md-6">
                                <label class="form-label" for="gender">Gender <span class="text-danger">*</span></label>
                                <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required @error('gender') aria-invalid="true" aria-describedby="gender-error" @enderror>
                                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender', $currentStudent?->gender ?? 'male') == $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('gender')
                                    <div id="gender-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="date_of_birth">Date of birth <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" placeholder="YYYY-MM-DD" value="{{ old('date_of_birth', $currentStudent?->date_of_birth) }}" autocomplete="bday" required aria-describedby="birth-date-help @error('date_of_birth') birth-date-error @enderror" @error('date_of_birth') aria-invalid="true" @enderror>
                                <div id="birth-date-help" class="form-text">Format: YYYY-MM-DD.</div>
                                @error('date_of_birth')
                                    <div id="birth-date-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="trn">TRN <span class="text-muted fw-normal fs-sm">(optional)</span></label>
                                <input type="text" class="form-control @error('trn') is-invalid @enderror" id="trn" name="trn" value="{{ old('trn', $currentStudent?->trn) }}" placeholder="TRN if available" aria-describedby="trn-help @error('trn') trn-error @enderror" @error('trn') aria-invalid="true" @enderror>
                                <div id="trn-help" class="form-text">Can be added later.</div>
                                @error('trn')
                                    <div id="trn-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="signature">Student signature
                                    @if (!$editing)
                                        <span class="text-danger">*</span>
                                    @else
                                        <span class="text-muted fw-normal fs-sm">(optional)</span>
                                    @endif
                                </label>
                                <input type="file" class="form-control @error('signature') is-invalid @enderror" id="signature" name="signature" accept=".jpg,.jpeg,.png" @required(!$editing) aria-describedby="signature-help @error('signature') signature-error @enderror" @error('signature') aria-invalid="true" @enderror>
                                <div id="signature-help" class="form-text">
                                    JPG/PNG, max 2 MB.
                                    @if ($editing)
                                        Leave blank to keep existing.
                                        @if ($student->signature)
                                            <span class="d-block">Current file: {{ $student->signature }}</span>
                                        @endif
                                    @endif
                                </div>
                                @error('signature')
                                    <div id="signature-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h2 class="block-title"><i class="fa fa-address-book text-muted me-2" aria-hidden="true"></i>Contact details</h2>
                    </div>
                    <div class="block-content block-content-full">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $currentStudent?->phone ?? '+265') }}" placeholder="+265" autocomplete="tel" required @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                                @error('phone')
                                    <div id="phone-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $currentStudent?->user?->email) }}" placeholder="student@example.com" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                                @error('email')
                                    <div id="email-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address">Street address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $currentStudent?->address) }}" autocomplete="street-address" required @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>
                                @error('address')
                                    <div id="address-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="district">District <span class="text-danger">*</span></label>
                                <select class="form-select @error('district') is-invalid @enderror" id="district" name="district" required @error('district') aria-invalid="true" aria-describedby="district-error" @enderror>
                                    @foreach ($district as $item)
                                        <option value="{{ $item->name }}" @selected(old('district', $currentStudent && $item->id == $currentStudent->district_id ? $item->name : null) == $item->name)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('district')
                                    <div id="district-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $editing ? 'col-12' : 'col-xl-4' }}">
                @if (!$editing)
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h2 class="block-title"><i class="fa fa-lock text-muted me-2" aria-hidden="true"></i>Login details</h2>
                        </div>
                        <div class="block-content block-content-full">
                            <div class="mb-4">
                                <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" autocomplete="off" required @error('username') aria-invalid="true" aria-describedby="username-error" @enderror>
                                @error('username')
                                    <div id="username-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                                @error('password')
                                    <div id="password-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
        <x-form-actions :cancel-url="$cancelUrl" />
    </form>
</div>

@push('scripts')
    <script>
        document.getElementById('student-form').addEventListener('submit', function () {
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Saving...';
        });
    </script>
@endpush
