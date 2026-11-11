<div class="content-side content-side-full">
    <script>
        const { createApp, ref,reactive, onMounted, watch, nextTick, computed, nBeforeUnmount, onUnmounted, toRaw } = Vue;

    </script>
    <ul class="nav-main Sidebar">
        {{-- Dashboard --}}
        <li>
            <a class="nav-main-link fw-semibold text-white tracking-wide" href="{{ url('/') }}">
                <i class="nav-main-link-icon fa fa-tachometer"></i>
                <span class="nav-main-link-name">Dashboard</span>
            </a>
        </li>

        {{-- Students --}}
        @php
            $studentRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view students'))
                ->pluck('name')->all();
            $invoiceRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view invoices'))
                ->pluck('name')->all();
        @endphp
        @role(array_values(array_unique(array_merge($studentRoles, $invoiceRoles))))
            @role($studentRoles)
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-graduate"></i>
                    <span class="nav-main-link-name">Students</span>
                </a>
                <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/students') }}">
                            <span class="nav-main-link-name">All students</span>
                        </a>
                    </li>
                    @can('create students')
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ route('students.create') }}">
                                <span class="nav-main-link-name">Add student</span>
                            </a>
                        </li>
                    @endcan
                    @endrole
                    @role($invoiceRoles)
                        <li>
                            <a class="nav-main-link" href="{{ url('/invoices') }}">
                                <span class="nav-main-link-name">Invoices</span>
                            </a>
                        </li>
                    @endrole
                    @role(['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'])
                        <li>
                            <a class="nav-main-link" href="{{ url('/payments') }}">
                                <span class="nav-main-link-name">Fees payments</span>
                            </a>
                        </li>
                    @endrole
                </ul>
            </li>
        @endrole

        {{-- Attendances --}}
        @role(['superAdmin', 'Super-Admin', 'admin', 'instructor', 'financeAdmin'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-clock"></i>
                    <span class="nav-main-link-name">Attendances</span>
                </a>
                <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/attendances') }}">
                            <span class="nav-main-link-name">All attendances</span>
                        </a>
                    </li>
                    @role(['superAdmin', 'Super-Admin', 'admin'])

                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/bulk-attendances') }}">
                            <span class="nav-main-link-name">Bulk attendances</span>
                        </a>
                    </li>
                    @can('manage operations')
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('bulk-attendances.create') }}">
                            <span class="nav-main-link-name">Add bulk attendance</span>
                        </a>
                    </li>
                    @endcan
                    @endrole
                </ul>
            </li>
        @endrole

        {{-- School --}}
        @php
            $courseRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view courses'))
                ->pluck('name')->all();
            $lessonRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view lessons'))
                ->pluck('name')->all();
            $classroomRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view classrooms'))
                ->pluck('name')->all();
            $fleetRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view fleet'))
                ->pluck('name')->all();
            $expenseTypeRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view expense types'))
                ->pluck('name')->all();
            $expenseRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view expenses'))
                ->pluck('name')->all();
            $expenseCreationRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'create expenses'))
                ->pluck('name')->all();
            $expensePaymentRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view expense payments'))
                ->pluck('name')->all();
            $expensePaymentEntryRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'record expense payments'))
                ->pluck('name')->all();
        @endphp
        @role(array_values(array_unique(array_merge(['superAdmin', 'Super-Admin', 'admin'], $courseRoles, $lessonRoles, $classroomRoles))))
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-bar-chart"></i>
                        <span class="nav-main-link-name">School</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @role(['superAdmin', 'Super-Admin', 'admin'])
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Departments</span></a></li>
                        @endrole
                        @role($classroomRoles)
                        <li><a class="nav-main-link" href="{{ route('classrooms.index') }}"><span class="nav-main-link-name">Classrooms</span></a></li>
                        @endrole
                        @role($courseRoles)
                        <li><a class="nav-main-link" href="{{ url('/courses') }}"><span class="nav-main-link-name">Courses</span></a></li>
                        @endrole
                        @role(['superAdmin', 'Super-Admin', 'admin'])
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Training levels</span></a></li>
                        @endrole
                        @role($lessonRoles)
                        <li><a class="nav-main-link" href="{{ url('/lessons') }}"><span class="nav-main-link-name">Lessons</span></a></li>
                        @endrole
                        @role(['superAdmin', 'Super-Admin', 'admin'])
                        <li><a class="nav-main-link" href="{{ url('/#') }}"><span class="nav-main-link-name">Satelites</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/driving-license-classes') }}"><span class="nav-main-link-name">Driving license classes</span></a></li>
                        @endrole
                    </ul>
                </li>
        @endrole

        @role(array_values(array_unique(array_merge(['superAdmin', 'Super-Admin', 'admin'], $fleetRoles))))
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-car"></i>
                        <span class="nav-main-link-name">Fleet</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @role($fleetRoles)
                        <li><a class="nav-main-link" href="{{ url('/fleet') }}"><span class="nav-main-link-name">Fleet</span></a></li>
                        @endrole
                        @role(['superAdmin', 'Super-Admin', 'admin'])
                        <li><a class="nav-main-link" href="{{ url('/track-fleet') }}"><span class="nav-main-link-name">Track fleet</span></a></li>
                        @endrole
                    </ul>
                </li>
        @endrole

            {{-- Expenses --}}
        @role(array_values(array_unique(array_merge($expenseRoles, $expenseCreationRoles, $expenseTypeRoles, $expensePaymentRoles, $expensePaymentEntryRoles))))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                    <span class="nav-main-link-name">Expenses</span>
                </a>
                <ul class="nav-main-submenu">
                    @role($expenseRoles)
                    <li><a class="nav-main-link" href="{{ route('expenses.index') }}"><span class="nav-main-link-name">All expenses</span></a></li>
                    @endrole
                    @role($expenseCreationRoles)
                    @can('create expenses')
                        <li><a class="nav-main-link" href="{{ route('expenses.create') }}"><span class="nav-main-link-name">Add expense</span></a></li>
                    @endcan
                    @endrole
                    @role($expensePaymentEntryRoles)
                    @can('record expense payments')
                        <li><a class="nav-main-link" href="{{ url('/scan-to-pay') }}"><span class="nav-main-link-name">Scan to pay</span></a></li>
                    @endcan
                    @endrole
                    @role($expensePaymentRoles)
                        <li><a class="nav-main-link" href="{{ url('/expense-payments') }}"><span class="nav-main-link-name">Expense payments</span></a></li>
                    @endrole
                    @role($expenseTypeRoles)
                        <li><a class="nav-main-link" href="{{ url('/expense-types') }}"><span class="nav-main-link-name">Expense types</span></a></li>
                    @endrole

                </ul>
            </li>
        @endrole

        @php
            $roleManagementRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view roles'))
                ->pluck('name')->all();
        @endphp
        {{-- Users --}}
        @role(array_values(array_unique(array_merge(['superAdmin', 'Super-Admin', 'admin'], $roleManagementRoles))))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-users"></i>
                    <span class="nav-main-link-name">Users</span>
                </a>
                <ul class="nav-main-submenu">
                    @role(['superAdmin', 'Super-Admin'])
                        <li><a class="nav-main-link" href="{{ route('administrators.index') }}"><span class="nav-main-link-name">Administrators</span></a></li>
                    @endrole
                    @role(['superAdmin', 'Super-Admin', 'admin'])
                        <li><a class="nav-main-link" href="{{ route('instructors.index') }}"><span class="nav-main-link-name">Instructors</span></a></li>
                    @endrole
                    @role($roleManagementRoles)
                        <li><a class="nav-main-link" href="{{ route('roles.index') }}"><span class="nav-main-link-name">Roles &amp; permissions</span></a></li>
                    @endrole
                </ul>
            </li>
        @endrole

        {{-- HRM --}}
        @role(['superAdmin', 'Super-Admin', 'admin'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-tie"></i>
                    <span class="nav-main-link-name">HRM</span>
                </a>
                <ul class="nav-main-submenu">
                    <li><a class="nav-main-link" href="{{ url('/instructor-payments') }}"><span class="nav-main-link-name">Bonus Payments</span></a></li>
                </ul>
            </li>
        @endrole

        {{-- Announcements --}}
        @php
            $announcementRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view announcements'))
                ->pluck('name')->all();
        @endphp
        @role(array_values(array_unique(array_merge(['superAdmin', 'Super-Admin', 'admin'], $announcementRoles))))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                    <i class="nav-main-link-icon fa fa-envelope"></i>
                    <span class="nav-main-link-name">Announcements</span>
                </a>
                <ul class="nav-main-submenu">
                    @role($announcementRoles)
                    @can('send announcements')
                    <li>
                        <a class="nav-main-link"
                        href=""
                            data-bs-toggle="modal"
                            data-bs-target="#announcement-modal">
                            <span class="nav-main-link-name">
                                Make Announcement
                            </span>
                        </a>
                    </li>
                    @endcan
                    @endrole
                    @role(['superAdmin', 'Super-Admin', 'admin'])
                    <li><a class="nav-main-link" href="{{ url('/sms-templates') }}"><span class="nav-main-link-name">Templates</span></a></li>
                    <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Configuration</span></a></li>
                    @endrole
                </ul>
            </li>
        @endrole

        {{-- Settings --}}
        @php
            $settingsRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view settings'))
                ->pluck('name')->all();
        @endphp
        @role($settingsRoles)
            <li>
                <a class="nav-main-link" href="{{ url('/settings') }}">
                    <i class="nav-main-link-icon fa fa-cogs"></i>
                    <span class="nav-main-link-name">Settings</span>
                </a>
            </li>
        @endrole

        @php
            $scheduleRoles = auth()->user()->roles
                ->filter(fn ($role) => in_array($role->name, ['superAdmin', 'Super-Admin'], true)
                    || $role->permissions->contains('name', 'view schedules'))
                ->pluck('name')->all();
        @endphp
        @role($scheduleRoles)
            <li>
                <a class="nav-main-link" href="{{ route('schedules.index') }}">
                    <i class="nav-main-link-icon fa fa-calendar-alt"></i>
                    <span class="nav-main-link-name">Schedules</span>
                    <span class="badge bg-danger ms-1">New</span>
                </a>
            </li>
        @endrole

        {{-- Instructor Quick Tools --}}
        @role('instructor')
            <li>
                <a class="nav-main-link" href="{{ url('/scanqrcode') }}">
                    <i class="nav-main-link-icon fa fa-qrcode"></i>
                    <span class="nav-main-link-name">Scan for attendance</span>
                </a>
            </li>
            @include('partials.location')
        @endrole

        {{-- Knowledge Navigation --}}
        @include('layouts.partials.knowledgeNav')
    </ul>
</div>
