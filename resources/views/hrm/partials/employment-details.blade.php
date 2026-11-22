<div class="row g-4">
    <div class="col-md-4">
        <div class="form-floating">
            <input type="text"
                   class="form-control js-datepicker @error('employment_date') is-invalid @enderror"
                   id="employment_date"
                   name="employment_date"
                   placeholder="YYYY-MM-DD"
                   autocomplete="off"
                   value="{{ old('employment_date', isset($employee?->employment_date) ? \Illuminate\Support\Carbon::parse($employee->employment_date)->format('Y-m-d') : '') }}">
            <label for="employment_date">Employment date</label>
            @error('employment_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating">
            <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type">
                <option value="">Select employment type</option>
                @foreach (['Full time', 'Contract', 'Part-time'] as $employmentType)
                    <option value="{{ $employmentType }}" {{ old('employment_type', $employee?->employment_type) === $employmentType ? 'selected' : '' }}>
                        {{ $employmentType }}
                    </option>
                @endforeach
            </select>
            <label for="employment_type">Employment type</label>
            @error('employment_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating">
            <input type="text"
                   class="form-control js-datepicker @error('end_date') is-invalid @enderror"
                   id="end_date"
                   name="end_date"
                   placeholder="YYYY-MM-DD"
                   autocomplete="off"
                   value="{{ old('end_date', isset($employee?->end_date) ? \Illuminate\Support\Carbon::parse($employee->end_date)->format('Y-m-d') : '') }}">
            <label for="end_date">End date</label>
            @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @if(!empty($statusOptions))
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" {{ !empty($statusRequired) ? 'required' : '' }}>
                    @foreach ($statusOptions as $statusValue => $statusLabel)
                        @php($value = is_int($statusValue) ? $statusLabel : $statusValue)
                        @php($label = $statusLabel)
                        <option value="{{ $value }}" {{ (string) old('status', $statusSelected ?? '') === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <label for="status">Status</label>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            $(function () {
                if ($.fn.datepicker) {
                    $('.js-datepicker').each(function () {
                        if (!$(this).data('datepicker')) {
                            $(this).datepicker({
                                format: 'yyyy-mm-dd',
                                autoclose: true,
                                todayHighlight: true
                            });
                        }
                    });
                }
            });
        </script>
    @endpush
@endonce
