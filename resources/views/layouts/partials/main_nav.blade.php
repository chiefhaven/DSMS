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
        @canany(['view students', 'view invoices', 'view fee payments'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-graduate"></i>
                    <span class="nav-main-link-name">Students</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view students')
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
                    @endcan
                    @can('view invoices')
                        <li>
                            <a class="nav-main-link" href="{{ url('/invoices') }}">
                                <span class="nav-main-link-name">Invoices</span>
                            </a>
                        </li>
                    @endcan
                    @can('view fee payments')
                        <li>
                            <a class="nav-main-link" href="{{ url('/payments') }}">
                                <span class="nav-main-link-name">Fees payments</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Attendances --}}
        @if (auth()->user()->can('view attendances') || (auth()->user()->can('view bulk attendances') && auth()->user()->can('view students')))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-clock"></i>
                    <span class="nav-main-link-name">Attendances</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view attendances')
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/attendances') }}">
                            <span class="nav-main-link-name">All attendances</span>
                        </a>
                    </li>
                    @endcan
                    @can('view bulk attendances')
                    @can('view students')
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/bulk-attendances') }}">
                            <span class="nav-main-link-name">Bulk attendances</span>
                        </a>
                    </li>
                    @can('create bulk attendances')
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('bulk-attendances.create') }}">
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
            $schoolPlaceholderRoles = ['superAdmin', 'Super-Admin', 'admin'];
        @endphp
        @if (auth()->user()->canAny(['view classrooms', 'view courses', 'view lessons']) || auth()->user()->hasAnyRole($schoolPlaceholderRoles))
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-bar-chart"></i>
                        <span class="nav-main-link-name">School</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @role($schoolPlaceholderRoles)
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Departments</span></a></li>
                        @endrole
                        @can('view classrooms')
                        <li><a class="nav-main-link" href="{{ route('classrooms.index') }}"><span class="nav-main-link-name">Classrooms</span></a></li>
                        @endcan
                        @can('view courses')
                        <li><a class="nav-main-link" href="{{ url('/courses') }}"><span class="nav-main-link-name">Courses</span></a></li>
                        @endcan
                        @role($schoolPlaceholderRoles)
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Training levels</span></a></li>
                        @endrole
                        @can('view lessons')
                        <li><a class="nav-main-link" href="{{ url('/lessons') }}"><span class="nav-main-link-name">Lessons</span></a></li>
                        @endcan
                        @role($schoolPlaceholderRoles)
                        <li><a class="nav-main-link" href="{{ url('/#') }}"><span class="nav-main-link-name">Satelites</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/driving-license-classes') }}"><span class="nav-main-link-name">Driving license classes</span></a></li>
                        @endrole
                    </ul>
                </li>
        @endif

        @canany(['view fleet', 'view vehicle locations'])
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-car"></i>
                        <span class="nav-main-link-name">Fleet</span>
                    </a>
                    <ul class="nav-main-submenu">
                        @can('view fleet')
                        <li><a class="nav-main-link" href="{{ url('/fleet') }}"><span class="nav-main-link-name">Fleet</span></a></li>
                        @endcan
                        @can('view vehicle locations')
                        <li><a class="nav-main-link" href="{{ url('/track-fleet') }}"><span class="nav-main-link-name">Track fleet</span></a></li>
                        @endcan
                    </ul>
                </li>
        @endcanany

            {{-- Expenses --}}
        @canany(['view expenses', 'create expenses', 'view expense types', 'view expense payments', 'record expense payments'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                    <span class="nav-main-link-name">Expenses</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view expenses')
                    <li><a class="nav-main-link" href="{{ route('expenses.index') }}"><span class="nav-main-link-name">All expenses</span></a></li>
                    @endcan
                    @can('create expenses')
                        <li><a class="nav-main-link" href="{{ route('expenses.create') }}"><span class="nav-main-link-name">Add expense</span></a></li>
                    @endcan
                    @can('record expense payments')
                        <li><a class="nav-main-link" href="{{ url('/scan-to-pay') }}"><span class="nav-main-link-name">Scan to pay</span></a></li>
                    @endcan
                    @can('view expense payments')
                        <li><a class="nav-main-link" href="{{ url('/expense-payments') }}"><span class="nav-main-link-name">Expense payments</span></a></li>
                    @endcan
                    @can('view expense types')
                        <li><a class="nav-main-link" href="{{ url('/expense-types') }}"><span class="nav-main-link-name">Expense types</span></a></li>
                    @endcan

                </ul>
            </li>
        @endcanany

        {{-- Users --}}
        @canany(['view roles', 'view instructors', 'view administrators'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-users"></i>
                    <span class="nav-main-link-name">Users</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view administrators')
                        <li><a class="nav-main-link" href="{{ route('administrators.index') }}"><span class="nav-main-link-name">Administrators</span></a></li>
                    @endcan
                    @can('view instructors')
                        <li><a class="nav-main-link" href="{{ route('instructors.index') }}"><span class="nav-main-link-name">Instructors</span></a></li>
                    @endcan
                    @can('view roles')
                        <li><a class="nav-main-link" href="{{ route('roles.index') }}"><span class="nav-main-link-name">Roles &amp; permissions</span></a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- HRM --}}
        @if (auth()->user()->canAny(['view instructor payments', 'view payroll', 'view hr departments', 'view designations']))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-user-tie"></i>
                    <span class="nav-main-link-name">HRM</span>
                </a>
                <ul class="nav-main-submenu">
                    @can('view payroll')
                    <li><a class="nav-main-link" href="{{ route('payroll.index') }}"><span class="nav-main-link-name">HR/Payroll</span></a></li>
                    @endcan
                    @can('view hr departments')
                    <li><a class="nav-main-link" href="{{ route('hr-departments.index') }}"><span class="nav-main-link-name">Departments</span></a></li>
                    @endcan
                    @can('view designations')
                    <li><a class="nav-main-link" href="{{ route('designations.index') }}"><span class="nav-main-link-name">Designations</span></a></li>
                    @endcan
                    @can('view instructor payments')
                    <li><a class="nav-main-link" href="{{ url('/instructor-payments') }}"><span class="nav-main-link-name">Bonus Payments</span></a></li>
                    @endcan
                </ul>
            </li>
        @endif

        {{-- Announcements --}}
        @if (auth()->user()->canAny(['view announcements', 'send announcements', 'view notification templates']) || auth()->user()->hasAnyRole($schoolPlaceholderRoles))
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                    <i class="nav-main-link-icon fa fa-envelope"></i>
                    <span class="nav-main-link-name">Announcements</span>
                </a>
                <ul class="nav-main-submenu">
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
                    @can('view notification templates')
                    <li><a class="nav-main-link" href="{{ url('/sms-templates') }}"><span class="nav-main-link-name">Templates</span></a></li>
                    @endcan
                    @role($schoolPlaceholderRoles)
                    <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Configuration</span></a></li>
                    @endrole
                </ul>
            </li>
        @endif

        {{-- Settings --}}
        @can('view settings')
            <li>
                <a class="nav-main-link" href="{{ url('/settings') }}">
                    <i class="nav-main-link-icon fa fa-cogs"></i>
                    <span class="nav-main-link-name">Settings</span>
                </a>
            </li>
        @endcan

        @can('view schedules')
            <li>
                <a class="nav-main-link" href="{{ route('schedules.index') }}">
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
                <a class="nav-main-link" href="{{ url('/scanqrcode') }}">
                    <i class="nav-main-link-icon fa fa-qrcode"></i>
                    <span class="nav-main-link-name">Scan for attendance</span>
                </a>
            </li>
            @endcan
        @endrole
        @can('record vehicle locations')
            @include('partials.location')
        @endcan

        {{-- Knowledge Navigation --}}
        @include('layouts.partials.knowledgeNav')
    </ul>
</div>
