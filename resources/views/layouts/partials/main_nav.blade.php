<div class="content-side content-side-full">
    <script>
        const { createApp, ref,reactive, onMounted, watch, nextTick, computed, nBeforeUnmount, onUnmounted, toRaw } = Vue;

    </script>
    @php
        $isDashboardActive = request()->routeIs('dashboard.home') || request()->is('/');
        $isStudentTicketsActive = request()->routeIs('student.tickets.*');
        $isAdminTicketsActive = request()->routeIs('admin.tickets.*');

        $isStudentsMenuActive = request()->is('students*') || request()->is('invoices*') || request()->is('payments*');
        $isStudentsActive = request()->is('students') || request()->is('students/*');
        $isAddStudentActive = request()->routeIs('students.create');
        $isInvoicesActive = request()->is('invoices*');
        $isPaymentsActive = request()->is('payments*');

        $isAttendancesMenuActive = request()->is('attendances*') || request()->is('bulk-attendances*');
        $isAllAttendancesActive = request()->is('attendances') || request()->is('attendances/*');
        $isBulkAttendancesActive = request()->is('bulk-attendances*');

        $isSchoolMenuActive = request()->is('classrooms*') || request()->is('courses*') || request()->is('lessons*') || request()->is('driving-license-classes*');
        $isClassroomsActive = request()->is('classrooms*');
        $isCoursesActive = request()->is('courses*');
        $isLessonsActive = request()->is('lessons*');
        $isDrivingLicenseClassesActive = request()->is('driving-license-classes*');

        $isFleetMenuActive = request()->is('fleet*')
            || request()->is('track-fleet*')
            || request()->is('fleet-update-approvals*');
        $isFleetActive = request()->is('fleet') || request()->is('fleet/*');
        $isTrackFleetActive = request()->is('track-fleet*');
        $isMyVehicleActive = request()->is('my-vehicle*');
        $isFleetUpdateApprovalsActive = request()->is('fleet-update-approvals*');
        $isPracticalInstructor = auth()->check()
            && strtolower((string) (auth()->user()->Instructor?->department?->name ?? '')) === 'practical';

        $isExpensesMenuActive = request()->is('expenses*') || request()->is('scan-to-pay*') || request()->is('expense-payments*') || request()->is('expense-types*');
        $isAllExpensesActive = request()->routeIs('expenses.index') || request()->is('expenses/*/edit') || request()->is('expenses/*');
        $isAddExpenseActive = request()->routeIs('expenses.create');
        $isScanToPayActive = request()->is('scan-to-pay*');
        $isExpensePaymentsActive = request()->is('expense-payments*');
        $isExpenseTypesActive = request()->is('expense-types*');

        $isUsersMenuActive = request()->is('administrators*') || request()->is('instructors*') || request()->is('roles*');
        $isAdministratorsActive = request()->is('administrators*');
        $isInstructorsActive = request()->is('instructors*');
        $isRolesActive = request()->is('roles*');

        $isHrmMenuActive = request()->is('hrm/*') || request()->routeIs('payroll.*') || request()->routeIs('hr-departments.*') || request()->routeIs('designations.*') || request()->routeIs('leaves.*') || request()->routeIs('hrm.settings');
        $isPayrollActive = request()->routeIs('payroll.*') || request()->is('hrm/payroll*');
        $isHrDepartmentsActive = request()->routeIs('hr-departments.*') || request()->is('hrm/hr-departments*');
        $isDesignationsActive = request()->routeIs('designations.*') || request()->is('hrm/designations*');

        $isAnnouncementsMenuActive = request()->is('sms-templates*');
        $isSmsTemplatesActive = request()->is('sms-templates*');

        $isSettingsActive = request()->is('settings*');
        $isSchedulesActive = request()->routeIs('schedules.*') || request()->is('schedules*');
        $isScanAttendanceActive = request()->is('scanqrcode*') || request()->routeIs('attendances.scan*');
        $isMyHrmActive = request()->routeIs('instructor.my-hrm*') || request()->routeIs('instructor.leave-requests.*');
        $isKnowledgeActive = request()->is('knowledge*');
    @endphp
    <ul class="nav-main Sidebar">
        {{-- Dashboard --}}
        <li>
            <a class="nav-main-link fw-semibold text-white tracking-wide {{ $isDashboardActive ? 'active' : '' }}" href="{{ url('/') }}" @if($isDashboardActive) style="background-color: rgba(255,255,255,0.08);" @endif>
                <i class="nav-main-link-icon fa fa-tachometer"></i>
                <span class="nav-main-link-name">Dashboard</span>
            </a>
        </li>

        @role('student')
            @can('view student tickets')
                <li>
                    <a class="nav-main-link {{ $isStudentTicketsActive ? 'active' : '' }}" href="{{ route('student.tickets.index') }}">
                        <i class="nav-main-link-icon fa fa-life-ring"></i>
                        <span class="nav-main-link-name">My tickets</span>
                    </a>
                </li>
            @endcan
        @endrole

        @canany(['view all student tickets', 'manage student tickets'])
            <li>
                <a class="nav-main-link {{ $isAdminTicketsActive ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">
                    <i class="nav-main-link-icon fa fa-ticket-alt"></i>
                    <span class="nav-main-link-name">Student tickets</span>
                </a>
            </li>
        @endcan

        {{-- Students --}}
        @canany(['view students', 'view invoices', 'view fee payments'])
            <li class="nav-main-item {{ $isStudentsMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isStudentsMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isStudentsMenuActive ? 'true' : 'false' }}" href="#">
                    <i class="nav-main-link-icon fa fa-user-graduate"></i>
                    <span class="nav-main-link-name">Students</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view students')
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ ($isStudentsActive && !$isAddStudentActive) ? 'active' : '' }}" href="{{ url('/students') }}">
                            <span class="nav-main-link-name">All students</span>
                        </a>
                    </li>
                    @can('create students')
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ $isAddStudentActive ? 'active' : '' }}" href="{{ route('students.create') }}">
                                <span class="nav-main-link-name">Add student</span>
                            </a>
                        </li>
                    @endcan
                    @endcan
                    @can('view invoices')
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ $isInvoicesActive ? 'active' : '' }}" href="{{ url('/invoices') }}">
                                <span class="nav-main-link-name">Invoices</span>
                            </a>
                        </li>
                    @endcan
                    @can('view fee payments')
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ $isPaymentsActive ? 'active' : '' }}" href="{{ url('/payments') }}">
                                <span class="nav-main-link-name">Fees payments</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Attendances --}}
        @if (auth()->user()->can('view attendances') || (auth()->user()->can('view bulk attendances') && auth()->user()->can('view students')))
            <li class="nav-main-item {{ $isAttendancesMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isAttendancesMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isAttendancesMenuActive ? 'true' : 'false' }}" href="#">
                    <i class="nav-main-link-icon fa fa-user-clock"></i>
                    <span class="nav-main-link-name">Attendances</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view attendances')
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $isAllAttendancesActive && !$isBulkAttendancesActive ? 'active' : '' }}" href="{{ url('/attendances') }}">
                            <span class="nav-main-link-name">All attendances</span>
                        </a>
                    </li>
                    @endcan
                    @can('view bulk attendances')
                    @can('view students')
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ ($isBulkAttendancesActive && !request()->routeIs('bulk-attendances.create')) ? 'active' : '' }}" href="{{ url('/bulk-attendances') }}">
                            <span class="nav-main-link-name">Bulk attendances</span>
                        </a>
                    </li>
                    @can('create bulk attendances')
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ request()->routeIs('bulk-attendances.create') ? 'active' : '' }}" href="{{ route('bulk-attendances.create') }}">
                            <span class="nav-main-link-name">Add bulk attendance</span>
                        </a>
                    </li>
                    @endcan
                    @endcan
                    @endcan
                </ul>
            </li>
        @endif

        {{-- School --}}
        @php
            $schoolPlaceholderRoles = ['superAdmin', 'admin'];
        @endphp
        @if (auth()->user()->canAny(['view classrooms', 'view courses', 'view lessons']) || auth()->user()->hasAnyRole($schoolPlaceholderRoles))
                <li class="nav-main-item {{ $isSchoolMenuActive ? 'open' : '' }}">
                    <a class="nav-main-link nav-main-link-submenu {{ $isSchoolMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isSchoolMenuActive ? 'true' : 'false' }}" href="#">
                        <i class="nav-main-link-icon fa fa-bar-chart"></i>
                        <span class="nav-main-link-name">School</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @role($schoolPlaceholderRoles)
                        <li class="nav-main-item"><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Departments</span></a></li>
                        @endrole
                        @can('view classrooms')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isClassroomsActive ? 'active' : '' }}" href="{{ route('classrooms.index') }}"><span class="nav-main-link-name">Classrooms</span></a></li>
                        @endcan
                        @can('view courses')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isCoursesActive ? 'active' : '' }}" href="{{ url('/courses') }}"><span class="nav-main-link-name">Courses</span></a></li>
                        @endcan
                        @role($schoolPlaceholderRoles)
                        <li class="nav-main-item"><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Training levels</span></a></li>
                        @endrole
                        @can('view lessons')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isLessonsActive ? 'active' : '' }}" href="{{ url('/lessons') }}"><span class="nav-main-link-name">Lessons</span></a></li>
                        @endcan
                        @role($schoolPlaceholderRoles)
                        <li class="nav-main-item"><a class="nav-main-link" href="{{ url('/#') }}"><span class="nav-main-link-name">Satelites</span></a></li>
                        <li class="nav-main-item"><a class="nav-main-link {{ $isDrivingLicenseClassesActive ? 'active' : '' }}" href="{{ url('/driving-license-classes') }}"><span class="nav-main-link-name">Driving license classes</span></a></li>
                        @endrole
                    </ul>
                </li>
        @endif

        @if ($isPracticalInstructor)
            <li class="nav-main-item">
                <a class="nav-main-link {{ $isMyVehicleActive ? 'active' : '' }}" href="{{ route('my-vehicle') }}">
                    <i class="nav-main-link-icon fa fa-car" aria-hidden="true"></i>
                    <span class="nav-main-link-name">My vehicle</span>
                </a>
            </li>
        @endif

        @if (auth()->check() && (auth()->user()->canAny(['view fleet', 'view vehicle locations', 'approve fleet updates']) || $isPracticalInstructor))
                <li class="nav-main-item {{ $isFleetMenuActive ? 'open' : '' }}">
                    <a class="nav-main-link nav-main-link-submenu {{ $isFleetMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isFleetMenuActive ? 'true' : 'false' }}" href="#">
                        <i class="nav-main-link-icon fa fa-car"></i>
                        <span class="nav-main-link-name">Fleet</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @can('view fleet')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isFleetActive ? 'active' : '' }}" href="{{ url('/fleet') }}"><span class="nav-main-link-name">Fleet</span></a></li>
                        @endcan
                        @can('view vehicle locations')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isTrackFleetActive ? 'active' : '' }}" href="{{ url('/track-fleet') }}"><span class="nav-main-link-name">Track fleet</span></a></li>
                        @endcan
                        @can('approve fleet updates')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isFleetUpdateApprovalsActive ? 'active' : '' }}" href="{{ route('fleet-update-approvals.index') }}"><span class="nav-main-link-name">Fleet data</span></a></li>
                        @endcan
                    </ul>
                </li>
        @endif

            {{-- Expenses --}}
        @canany(['view expenses', 'create expenses', 'view expense types', 'view expense payments', 'record expense payments'])
            <li class="nav-main-item {{ $isExpensesMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isExpensesMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isExpensesMenuActive ? 'true' : 'false' }}" href="#">
                    <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                    <span class="nav-main-link-name">Expenses</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view expenses')
                    <li class="nav-main-item"><a class="nav-main-link {{ ($isAllExpensesActive && !$isAddExpenseActive) ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span class="nav-main-link-name">All expenses</span></a></li>
                    @endcan
                    @can('create expenses')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isAddExpenseActive ? 'active' : '' }}" href="{{ route('expenses.create') }}"><span class="nav-main-link-name">Add expense</span></a></li>
                    @endcan
                    @can('record expense payments')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isScanToPayActive ? 'active' : '' }}" href="{{ url('/scan-to-pay') }}"><span class="nav-main-link-name">Scan to pay</span></a></li>
                    @endcan
                    @can('view expense payments')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isExpensePaymentsActive ? 'active' : '' }}" href="{{ url('/expense-payments') }}"><span class="nav-main-link-name">Expense payments</span></a></li>
                    @endcan
                    @can('view expense types')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isExpenseTypesActive ? 'active' : '' }}" href="{{ url('/expense-types') }}"><span class="nav-main-link-name">Expense types</span></a></li>
                    @endcan

                </ul>
            </li>
        @endcanany

        {{-- Users --}}
        @canany(['view roles', 'view instructors', 'view administrators'])
            <li class="nav-main-item {{ $isUsersMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isUsersMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isUsersMenuActive ? 'true' : 'false' }}" href="#">
                    <i class="nav-main-link-icon fa fa-users"></i>
                    <span class="nav-main-link-name">Users</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view administrators')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isAdministratorsActive ? 'active' : '' }}" href="{{ route('administrators.index') }}"><span class="nav-main-link-name">Administrators</span></a></li>
                    @endcan
                    @can('view instructors')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isInstructorsActive ? 'active' : '' }}" href="{{ route('instructors.index') }}"><span class="nav-main-link-name">Instructors</span></a></li>
                    @endcan
                    @can('view roles')
                        <li class="nav-main-item"><a class="nav-main-link {{ $isRolesActive ? 'active' : '' }}" href="{{ route('roles.index') }}"><span class="nav-main-link-name">Roles &amp; permissions</span></a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- HRM --}}
        @if (auth()->user()->canAny(['view payroll', 'view hr departments', 'view designations', 'view leaves', 'manage leave types', 'review leave requests']))
            <li class="nav-main-item {{ $isHrmMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isHrmMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isHrmMenuActive ? 'true' : 'false' }}" href="#">
                    <i class="nav-main-link-icon fa fa-user-tie"></i>
                    <span class="nav-main-link-name">HRM</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view payroll')
                    <li class="nav-main-item"><a class="nav-main-link {{ $isPayrollActive ? 'active' : '' }}" href="{{ route('payroll.index') }}"><span class="nav-main-link-name">HR/Payroll</span></a></li>
                    @endcan
                    @canany(['view leaves', 'manage leave types', 'review leave requests', 'process payroll', 'edit instructors', 'manage administrator accounts'])
                    <li class="nav-main-item"><a class="nav-main-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}" href="{{ route('leaves.index') }}"><span class="nav-main-link-name">Leaves</span></a></li>
                    @endcanany
                    @can('view hr departments')
                    <li class="nav-main-item"><a class="nav-main-link {{ $isHrDepartmentsActive ? 'active' : '' }}" href="{{ route('hr-departments.index') }}"><span class="nav-main-link-name">Departments</span></a></li>
                    @endcan
                    @can('view designations')
                    <li class="nav-main-item"><a class="nav-main-link {{ $isDesignationsActive ? 'active' : '' }}" href="{{ route('designations.index') }}"><span class="nav-main-link-name">Designations</span></a></li>
                    @endcan
                </ul>
            </li>
        @endif

        {{-- Announcements --}}
        @if (auth()->user()->canAny(['view announcements', 'send announcements', 'view notification templates']) || auth()->user()->hasAnyRole($schoolPlaceholderRoles))
            <li class="nav-main-item {{ $isAnnouncementsMenuActive ? 'open' : '' }}">
                <a class="nav-main-link nav-main-link-submenu {{ $isAnnouncementsMenuActive ? 'active' : '' }}" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ $isAnnouncementsMenuActive ? 'true' : 'false' }}">
                    <i class="nav-main-link-icon fa fa-envelope"></i>
                    <span class="nav-main-link-name">Announcements</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('send announcements')
                    <li class="nav-main-item">
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
                    @can('view notification templates')
                    <li class="nav-main-item"><a class="nav-main-link {{ $isSmsTemplatesActive ? 'active' : '' }}" href="{{ url('/sms-templates') }}"><span class="nav-main-link-name">Templates</span></a></li>
                    @endcan
                    @role($schoolPlaceholderRoles)
                    <li class="nav-main-item"><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Configuration</span></a></li>
                    @endrole
                </ul>
            </li>
        @endif

        {{-- Settings --}}
        @can('view settings')
            <li>
                <a class="nav-main-link {{ $isSettingsActive ? 'active' : '' }}" href="{{ url('/settings') }}" @if($isSettingsActive) style="background-color: rgba(255,255,255,0.08);" @endif>
                    <i class="nav-main-link-icon fa fa-cogs"></i>
                    <span class="nav-main-link-name">Settings</span>
                </a>
            </li>
        @endcan

        @can('view schedules')
            <li>
                <a class="nav-main-link {{ $isSchedulesActive ? 'active' : '' }}" href="{{ route('schedules.index') }}" @if($isSchedulesActive) style="background-color: rgba(255,255,255,0.08);" @endif>
                    <i class="nav-main-link-icon fa fa-calendar-alt"></i>
                    <span class="nav-main-link-name">Schedules</span>
                    <span class="badge bg-danger ms-1">New</span>
                </a>
            </li>
        @endcan

        {{-- Instructor Quick Tools --}}
        @role('instructor')
            @can('record attendances')
            <li>
                <a class="nav-main-link {{ $isScanAttendanceActive ? 'active' : '' }}" href="{{ url('/scanqrcode') }}" @if($isScanAttendanceActive) style="background-color: rgba(255,255,255,0.08);" @endif>
                    <i class="nav-main-link-icon fa fa-qrcode"></i>
                    <span class="nav-main-link-name">Scan for attendance</span>
                </a>
            </li>
            @endcan
        @endrole

        @if(auth()->user()->instructor_id || auth()->user()->administrator_id)
            <li>
                <a class="nav-main-link {{ $isMyHrmActive ? 'active' : '' }}" href="{{ route('instructor.my-hrm') }}" @if($isMyHrmActive) style="background-color: rgba(255,255,255,0.08);" @endif>
                    <i class="nav-main-link-icon fa fa-id-badge"></i>
                    <span class="nav-main-link-name">My HRM</span>
                </a>
            </li>
        @endif

        @role('instructor')
            @can('record vehicle locations')
                @include('partials.location')
            @endcan
        @endrole

        {{-- Knowledge Navigation --}}
        @include('layouts.partials.knowledgeNav')
    </ul>
</div>
