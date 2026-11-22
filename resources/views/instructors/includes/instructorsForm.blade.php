<h2 class="content-heading pt-0">
    <i class="fa fa-fw fa-user text-muted me-1"></i> Instructor Information
</h2>
<div class="row push">
    <div class="col-lg-4">
        <p class="text-muted">
            Instructor details
        </p>
    </div>
    <div class="col-md-8 col-xl-8">
        <div class="row haven-floating">
            <!-- First Name -->
            <div class="col-6 form-floating mb-4">
                <input type="text"
                        class="form-control"
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name', optional($instructor)->fname) }}"
                        placeholder="Instructor's first name" required>
                <label class="form-label" for="first_name">First name</label>
            </div>

            <!-- Sir Name -->
            <div class="col-6 form-floating mb-4">
                <input type="text"
                        class="form-control"
                        id="sir_name"
                        name="sir_name"
                        value="{{ old('sir_name', optional($instructor)->sname) }}"
                        placeholder="Sirname" required>
                <label class="form-label" for="sir_name">Sirname</label>
            </div>
        </div>

        <!-- Gender -->
        <div class="col-12 form-floating mb-4">
            <select class="form-select" id="gender" name="gender" required>
                <option value="male" {{ old('gender', optional($instructor)->gender) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', optional($instructor)->gender) == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', optional($instructor)->gender) == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            <label for="gender">Gender</label>
        </div>

        <!-- Date of Birth -->
        <div class="mb-4 form-floating">
            <input type="text"
                    class="form-control @error('date_of_birth') is-invalid @enderror"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', optional($instructor)->date_of_birth) }}"
                    required>
            <label class="form-label" for="date_of_birth">Date of birth</label>
        </div>

        <div class="mb-4 foam-floating">
            <label for="signature" class="form-label">Signature</label>

            <input type="file"
                class="form-control @error('signature') is-invalid @enderror"
                id="signature"
                name="signature"
                accept="image/*"
            >

            @error('signature')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            {{-- Existing signature preview --}}
            @if(optional($instructor)->signature)
                <div class="mt-2">
                    <small class="text-muted">Current Signature:</small><br>
                    <img
                        src="{{ asset('media/signatures/' . $instructor->signature) }}"
                        alt="Instructor Signature"
                        style="height:30px; width:auto;"
                    >
                </div>
            @endif

        </div>


        <div class="row haven-floating">
            <!-- Phone -->
            <div class="mb-4 col-md-6 form-floating">
                <input type="text"
                        class="form-control"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', optional($instructor)->phone) }}"
                        placeholder="+265" required>
                <label class="form-label" for="phone">Phone</label>
            </div>

            <!-- Email -->
            <div class="mb-4 col-md-6 form-floating">
                <input
                    type="email"
                    class="form-control"
                    id="email_address"
                    name="email"
                    value="{{ old('email', $instructor?->user->email) }}"
                    placeholder="Instructor's email address"
                    @if($instructor && !auth()->user()->can('manage instructor accounts')) readonly @endif
                    required>
                <label for="email_address">Email address</label>
            </div>
        </div>

        <div class="row haven-floating">
            <!-- Address -->
            <div class="mb-4 col-md-6 form-floating">
                <input type="text"
                        class="form-control"
                        id="address"
                        name="address"
                        value="{{ old('address', optional($instructor)->address) }}"
                        placeholder="Address"
                        required>
                <label class="form-label" for="address">Street address</label>
            </div>

            <!-- District -->
            <div class="form-floating col-md-6 mb-4">
                <select class="form-select" id="district" name="district" required>
                    @foreach ($district as $district)
                        <option value="{{ $district->name }}"
                                {{ old('district', optional($instructor)->district) == $district->name ? 'selected' : '' }}>
                            {{ $district->name }}
                        </option>
                    @endforeach
                </select>
                <label for="district">District</label>
            </div>
        </div>

        <div class="row haven-floating">
            <!-- Department -->
            <div class="form-floating col-md-6 mb-4">
                <select class="form-select" id="department" name="department" required>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                                {{ old('department', optional($instructor)->department_id) == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                <label for="department">Department assigned</label>
            </div>

            <div class="mb-4 col-md-6 form-floating">
                <select class="form-select" id="status" name="status" required>
                    <option value="active" {{ old('status', optional($instructor)->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ old('status', optional($instructor)->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="suspended" {{ old('status', optional($instructor)->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="contract ended" {{ old('status', optional($instructor)->status) == 'contract_ended' ? 'selected' : '' }}>Contract Ended</option>
                </select>
                <label for="status">Status</label>
            </div>
        </div>
    </div>

    <div class="content-heading"><p>&nbsp;</p></div>

    @if(($hrDepartments ?? null) !== null && auth()->user()->can('process payroll'))
    <div class="col-lg-4">
        <p class="text-muted">
            HRM — HR department, designation and salary grade
        </p>
    </div>

    <div class="col-lg-8 col-xl-5">
        <div class="form-floating mb-4">
            <select class="form-select" id="hr_department_id" name="hr_department_id" onchange="filterDesignationOptions(true)">
                <option value="" {{ old('hr_department_id', optional($instructor)->hr_department_id) ? '' : 'selected' }}>No department</option>
                @foreach ($hrDepartments as $hrDepartment)
                    <option value="{{ $hrDepartment->id }}"
                            {{ old('hr_department_id', optional($instructor)->hr_department_id) == $hrDepartment->id ? 'selected' : '' }}>
                        {{ $hrDepartment->name }}
                    </option>
                @endforeach
            </select>
            <label for="hr_department_id">HR department</label>
        </div>
        <div class="form-floating mb-4">
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
        <div class="form-floating mb-2">
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
        <div class="form-text mb-4">Selecting a grade sets its basic salary as the starting amount. You can edit the amount below.</div>
        <div class="form-floating mb-4">
            <input type="number" step="0.01" min="0" class="form-control" id="basic_salary" name="basic_salary"
                   placeholder="0.00" value="{{ old('basic_salary', $instructor?->basic_salary ?? $instructor?->salaryGrade?->basic_salary) }}">
            <label class="form-label" for="basic_salary">Basic salary (K)</label>
        </div>
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
    </div>

    <div class="content-heading"><p>&nbsp;</p></div>
    @endif

    <div class="col-lg-4">
        <p class="text-muted">
            Login details
        </p>
    </div>

    <div class="col-lg-8 col-xl-5">
        <!-- Username -->
        <div class="form-floating mb-4">
            <input type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    value="{{ old('username', $instructor?->user->name) }}"
                    placeholder="Instructor's username"
                    required>
            <label for="username">Username</label>
        </div>

        <!-- Password -->
        @if(!$instructor || auth()->user()->can('manage instructor accounts'))
        <div class="form-floating mb-4 position-relative">
            <input type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Enter your password">
            <label for="password">Password</label>
            <button type="button"
                    class="btn btn-sm btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                    onclick="togglePasswordVisibility()">
                Show
            </button>
        </div>
        @endif

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
    </div>
</div>
