<div class="content-side content-side-full">
    <script>
        const { createApp, ref, onMounted, watch, nextTick, computed, nBeforeUnmount } = Vue;

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
        @role(['superAdmin', 'admin', 'instructor', 'financeAdmin'])
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
                    @role(['superAdmin', 'admin'])
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/addstudent') }}">
                            <span class="nav-main-link-name">Add student</span>
                        </a>
                    </li>
                    @endrole
                </ul>
            </li>

            {{-- Attendances --}}
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
                    @role(['superAdmin', 'admin'])
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/schedules') }}">
                            <span class="nav-main-link-name">Schedules</span>
                            <span class="badge bg-danger ms-1">New</span>
                        </a>
                    </li>
                    @endrole
                </ul>
            </li>
        @endrole

        {{-- School --}}
        @role(['superAdmin', 'admin'])
            @role(['superAdmin', 'admin'])
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-bar-chart"></i>
                        <span class="nav-main-link-name">School</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Departments</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/classes') }}"><span class="nav-main-link-name">Classrooms</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/courses') }}"><span class="nav-main-link-name">Courses</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Training levels</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/lessons') }}"><span class="nav-main-link-name">Lessons</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/fleet') }}"><span class="nav-main-link-name">Fleet</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/#') }}"><span class="nav-main-link-name">Satelites</span></a></li>
                    </ul>
                </li>

                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-car"></i>
                        <span class="nav-main-link-name">Fleet</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li><a class="nav-main-link" href="{{ url('/fleet') }}"><span class="nav-main-link-name">Fleet</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/track-fleet') }}"><span class="nav-main-link-name">Track fleet</span></a></li>
                    </ul>
                </li>

                {{-- Invoices --}}
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                        <span class="nav-main-link-name">Student billing</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li><a class="nav-main-link" href="{{ url('/invoices') }}"><span class="nav-main-link-name">Invoices</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/payments') }}"><span class="nav-main-link-name">Student payments</span></a></li>
                    </ul>
                </li>
                {{--  <li>
                    <a class="nav-main-link" href="{{ url('/invoices') }}">
                        <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                        <span class="nav-main-link-name">Invoices</span>
                    </a>
                </li>  --}}
            @endrole

            {{-- Expenses --}}
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                    <span class="nav-main-link-name">Expenses</span>
                </a>
                <ul class="nav-main-submenu">
                    <li><a class="nav-main-link" href="{{ url('/expenses') }}"><span class="nav-main-link-name">All expenses</span></a></li>
                    @role(['superAdmin', 'admin'])
                        <li><a class="nav-main-link" href="{{ url('/addexpense') }}"><span class="nav-main-link-name">Add expense</span></a></li>
                    @endrole
                    @role(['superAdmin', 'financeAdmin'])
                        <li><a class="nav-main-link" href="{{ url('/scan-to-pay') }}"><span class="nav-main-link-name">Scan to pay</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/expense-payments') }}"><span class="nav-main-link-name">Expense payments</span></a></li>
                    @endrole

                </ul>
            </li>
            @role(['superAdmin', 'admin'])
                {{-- Instructors --}}
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                        <i class="nav-main-link-icon fa fa-users"></i>
                        <span class="nav-main-link-name">Instructors</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li><a class="nav-main-link" href="{{ url('/instructors') }}"><span class="nav-main-link-name">All instructors</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/addinstructor') }}"><span class="nav-main-link-name">Add instructor</span></a></li>
                        <li><a class="nav-main-link" href="{{ url('/instructor-payments') }}"><span class="nav-main-link-name">Bonus payments</span></a></li>
                    </ul>
                </li>
            @endrole
        @endrole

        {{-- Administrators --}}
        @role('superAdmin')
        <li class="nav-main-item">
            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                <i class="nav-main-link-icon fa fa-users"></i>
                <span class="nav-main-link-name">Administrators</span>
            </a>
            <ul class="nav-main-submenu">
                <li><a class="nav-main-link" href="{{ url('/administrators') }}"><span class="nav-main-link-name">All administrators</span></a></li>
                <li><a class="nav-main-link" href="{{ url('/addadministrator') }}"><span class="nav-main-link-name">Add administrator</span></a></li>
            </ul>
        </li>
        @endrole

        {{-- Announcements --}}
        @role(['superAdmin', 'admin'])
        <li class="nav-main-item">
            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                <i class="nav-main-link-icon fa fa-envelope"></i>
                <span class="nav-main-link-name">Announcements</span>
            </a>
            <ul class="nav-main-submenu">
                <li><a class="nav-main-link" href="{{ url('/send-announcement') }}"><span class="nav-main-link-name">Make Announcement</span></a></li>
                <li><a class="nav-main-link" href="{{ url('/sms-templates') }}"><span class="nav-main-link-name">Templates</span></a></li>
                <li><a class="nav-main-link" href="{{ url('#') }}"><span class="nav-main-link-name">Configuration</span></a></li>
            </ul>
        </li>
        @endrole

        {{-- Settings --}}
        @role('superAdmin')
        <li>
            <a class="nav-main-link" href="{{ url('/settings') }}">
                <i class="nav-main-link-icon fa fa-cogs"></i>
                <span class="nav-main-link-name">Settings</span>
            </a>
        </li>
        <li>
            <a class="nav-main-link" href="{{ url('#') }}">
                <i class="nav-main-link-icon fa fa-user-tag"></i>
                <span class="nav-main-link-name">Roles & permissions</span>
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
            <li>
                <a class="nav-main-link" href="{{ url('/schedule-lesson-index') }}">
                    <i class="nav-main-link-icon fa fa-calendar-alt"></i>
                    <span class="nav-main-link-name">Schedule lesson</span>
                    <span class="badge bg-danger ms-1">New</span>
                </a>
            </li>

            @include('partials.location')
        @endrole

        {{-- Knowledge Navigation --}}
        @include('layouts.partials.knowledgeNav')
    </ul>
</div>
