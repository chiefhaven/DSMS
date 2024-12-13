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
                    value="
                    {{ old('date_of_birth', optional($instructor)->date_of_birth) }}"

                    required>
            <label class="form-label" for="date_of_birth">Date of birth</label>
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

        <script>
            function togglePasswordVisibility() {
                const passwordInput = document.getElementById('password');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
            }

            // Set the datepicker with current date as default value
            $(document).ready(function() {
                var instructorDateOfBirth = "{{ $instructor?->date_of_birth }}"; // Get date_of_birth as a string from Blade

                var today = instructorDateOfBirth && instructorDateOfBirth.trim() !== ""
                    ? moment(instructorDateOfBirth, "YYYY-MM-DD").toDate()
                    : new Date(); // If date_of_birth is not available, use the current date

                // Extract day, month, and year with leading zeroes
                var day = ("0" + today.getDate()).slice(-2);
                var month = ("0" + (today.getMonth() + 1)).slice(-2); // Months are zero-based
                var year = today.getFullYear();

                // Set the datepicker
                $("#date_of_birth").datepicker({
                    format: "dd-mm-yyyy",
                    autoclose: true,
                    todayHighlight: true
                }).datepicker('setDate', day + '/' + month + '/' + year); // Use `/` for proper format
            });
        </script>
    </div>
</div>
