<div class="block block-rounded">
    <div class="block-content">
        <div class="row g-4">
    <div class="col-12">
        <section class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                    <i class="fa fa-user"></i>
                </span>
                <h3 class="fs-5 fw-semibold mb-0">Personal information</h3>
            </div>
            <div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name"
                                   value="{{ old('first_name', optional($instructor)->fname) }}" placeholder="First name" required>
                            <label for="first_name">First name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('sir_name') is-invalid @enderror" id="sir_name" name="sir_name"
                                   value="{{ old('sir_name', optional($instructor)->sname) }}" placeholder="Surname" required>
                            <label for="sir_name">Surname</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                <option value="male" {{ old('gender', optional($instructor)->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', optional($instructor)->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', optional($instructor)->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <label for="gender">Gender</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth"
                                   value="{{ old('date_of_birth', optional($instructor)->date_of_birth) }}" placeholder="DD-MM-YYYY" required>
                            <label for="date_of_birth">Date of birth (DD-MM-YYYY)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                   value="{{ old('phone', optional($instructor)->phone) }}" placeholder="+265" required>
                            <label for="phone">Phone</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select @error('district') is-invalid @enderror" id="district" name="district" required>
                                @foreach ($district as $districtOption)
                                    <option value="{{ $districtOption->name }}"
                                            {{ old('district', optional($instructor)->district) === $districtOption->name ? 'selected' : '' }}>
                                        {{ $districtOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="district">District</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address"
                                   value="{{ old('address', optional($instructor)->address) }}" placeholder="Street address" required>
                            <label for="address">Street address</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select @error('department') is-invalid @enderror" id="department" name="department" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                            {{ old('department', optional($instructor)->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="department">Academic department</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="file" class="form-control @error('signature') is-invalid @enderror" id="signature" name="signature" accept="image/*">
                            <label for="signature">Signature</label>
                            @error('signature')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @if (optional($instructor)->signature)
                            <div class="mt-2">
                                <small class="text-muted">Current signature</small><br>
                                <img src="{{ asset('media/signatures/' . $instructor->signature) }}" alt="Instructor signature" style="height: 30px; width: auto;">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                    <i class="fa fa-briefcase"></i>
                </span>
                <h3 class="fs-5 fw-semibold mb-0">HRM &amp; employment</h3>
            </div>
            <div>
                <p class="text-muted fs-sm mb-3">Employment dates, contract type, terms of employment, and staff status.</p>
                @include('hrm.partials.employment-details', [
                    'employee' => $instructor,
                    'statusOptions' => ['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'Contract ended' => 'Contract ended'],
                    'statusSelected' => old('status', optional($instructor)->status),
                    'statusRequired' => true,
                ])
                @include('hrm.partials.terms-of-employment', ['employee' => $instructor, 'staffType' => 'instructor'])

                <div class="row g-3 mt-4">
                </div>

                @if (($hrDepartments ?? null) !== null && auth()->user()->can('process payroll'))
                    <div class="row g-3 mt-4">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="hr_department_id" name="hr_department_id" onchange="filterDesignationOptions(true)">
                                    <option value="" {{ old('hr_department_id', optional($instructor)->hr_department_id) ? '' : 'selected' }}>No department</option>
                                    @foreach ($hrDepartments as $hrDepartment)
                                        <option value="{{ $hrDepartment->id }}" {{ old('hr_department_id', optional($instructor)->hr_department_id) == $hrDepartment->id ? 'selected' : '' }}>
                                            {{ $hrDepartment->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="hr_department_id">HR department</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="designation_id" name="designation_id">
                                    <option value="" {{ old('designation_id', optional($instructor)->designation_id) ? '' : 'selected' }}>No designation</option>
                                    @foreach ($designations ?? [] as $designation)
                                        <option value="{{ $designation->id }}" data-department="{{ $designation->hr_department_id }}"
                                                data-current="{{ old('designation_id', optional($instructor)->designation_id) == $designation->id ? 'true' : 'false' }}"
                                                {{ old('designation_id', optional($instructor)->designation_id) == $designation->id ? 'selected' : '' }}>
                                            {{ $designation->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="designation_id">Designation</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="salary_grade_id" name="salary_grade_id" onchange="setBasicSalaryFromGrade(this)">
                                    <option value="" {{ old('salary_grade_id', optional($instructor)->salary_grade_id) ? '' : 'selected' }}>No salary grade</option>
                                    @foreach ($salaryGrades ?? [] as $salaryGrade)
                                        <option value="{{ $salaryGrade->id }}" data-salary="{{ $salaryGrade->basic_salary }}"
                                                {{ old('salary_grade_id', optional($instructor)->salary_grade_id) == $salaryGrade->id ? 'selected' : '' }}>
                                            {{ $salaryGrade->code }} — {{ $salaryGrade->name }} (K{{ number_format((float) $salaryGrade->basic_salary, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <label for="salary_grade_id">Salary grade</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" class="form-control" id="basic_salary" name="basic_salary"
                                       placeholder="0.00" value="{{ old('basic_salary', $instructor?->basic_salary ?? $instructor?->salaryGrade?->basic_salary) }}">
                                <label for="basic_salary">Basic salary (K)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex align-items-center">
                                <div class="form-check mb-0">
                                    <input type="hidden" name="paye" value="0">
                                    <input class="form-check-input @error('paye') is-invalid @enderror" type="checkbox" value="1" id="paye" name="paye"
                                           {{ old('paye', isset($instructor) ? (bool) ($instructor->paye ?? false) : false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="paye">PAYE applicable</label>
                                    @error('paye')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex align-items-center">
                                <div class="form-check mb-0">
                                    <input type="hidden" name="pension_applicable" value="0">
                                    <input class="form-check-input @error('pension_applicable') is-invalid @enderror" type="checkbox" value="1" id="pension_applicable" name="pension_applicable"
                                           {{ old('pension_applicable', isset($instructor) ? (bool) ($instructor->pension_applicable ?? false) : false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="pension_applicable">Pension applicable</label>
                                    @error('pension_applicable')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-text mt-2">Selecting a grade sets its basic salary as the starting amount. You can edit the basic salary.</div>
                    <script>
                        function filterDesignationOptions(clearInvalidSelection) {
                            const departmentSelect = document.getElementById('hr_department_id');
                            const designationSelect = document.getElementById('designation_id');
                            const selectedOption = designationSelect.options[designationSelect.selectedIndex];

                            if (clearInvalidSelection && selectedOption.value
                                && selectedOption.dataset.department !== departmentSelect.value) {
                                designationSelect.value = '';
                            }

                            Array.from(designationSelect.options).forEach((option) => {
                                const matchesDepartment = option.dataset.department === departmentSelect.value;
                                const keepCurrent = !clearInvalidSelection && option.dataset.current === 'true';
                                option.hidden = Boolean(option.value) && !matchesDepartment && !keepCurrent;
                            });
                        }

                        function setBasicSalaryFromGrade(select) {
                            const salary = select.options[select.selectedIndex]?.dataset.salary;
                            if (salary !== undefined) {
                                document.getElementById('basic_salary').value = salary;
                            }
                        }

                        filterDesignationOptions(false);
                    </script>
                @endif

                @include('hrm.partials.bank-account-details', ['employee' => $instructor])
            </div>
        </section>
    </div>

    <div class="col-12">
        <section class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="rounded-circle bg-body-light text-primary p-2" aria-hidden="true">
                    <i class="fa fa-lock"></i>
                </span>
                <h3 class="fs-5 fw-semibold mb-0">Account &amp; access</h3>
            </div>
            <div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email_address" name="email"
                           value="{{ old('email', $instructor?->user?->email) }}" placeholder="Email address"
                           @if ($instructor && !auth()->user()->can('manage instructor accounts')) readonly @endif required>
                    <label for="email_address">Email address</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username"
                           value="{{ old('username', $instructor?->user?->name) }}" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>
                @if (!$instructor || auth()->user()->can('manage instructor accounts'))
                    <div class="form-floating position-relative">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password">
                        <label for="password">Password</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                onclick="togglePasswordVisibility()">Show</button>
                    </div>
                    @if ($instructor)
                        <div class="form-text">Leave blank to keep the current password.</div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    }

    $(document).ready(function() {
        $("#date_of_birth").datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
