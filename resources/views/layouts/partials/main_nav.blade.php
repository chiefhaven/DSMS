<div class="content-side content-side-full">

          <ul class="nav-main Sidebar">
            <li>
              <a class="nav-main-link" class="fw-semibold text-white tracking-wide" href="{{ url('/') }}">
                <i class="nav-main-link-icon fa fa-tachometer"></i>
                <span class="nav-main-link-name">Dashboard</span>
              </a>
            </li>
            @role(['superAdmin', 'admin', 'instructor'])
                <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                            <i class="nav-main-link-icon fa fa-user-graduate"></i>
                            <span class="nav-main-link-name">Students</span>
                        </a>
                        <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ url('/students') }}">
                                <span class="nav-main-link-name">All students</span>
                            </a>
                            </li>
                        </ul>
                        @role(['superAdmin', 'admin'])
                            <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="{{ url('/addstudent') }}">
                                    <span class="nav-main-link-name">Add student</span>
                                </a>
                                </li>
                            </ul>
                        @endcan
                </li>
                <li class="nav-main-item">
                    <a class="nav-main-link" href="{{ url('/attendances') }}">
                        <i class="nav-main-link-icon fa fa-user-clock"></i>
                        <span class="nav-main-link-name">Attendances</span>
                    </a>
                </li>
            @endcan

        @role(['superAdmin', 'admin'])
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                <i class="nav-main-link-icon fa fa-bar-chart"></i>
                <span class="nav-main-link-name">School</span>
                </a>
                <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('#') }}">
                            <span class="nav-main-link-name">Departments</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/classes') }}">
                            <span class="nav-main-link-name">Classes</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/courses') }}">
                            <span class="nav-main-link-name">Courses</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/lessons') }}">
                            <span class="nav-main-link-name">Lessons</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/fleet') }}">
                            <span class="nav-main-link-name">Fleet</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                <span class="nav-main-link-name">Expenses</span>
                </a>
                <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/expenses') }}">
                            <span class="nav-main-link-name">All expenses</span>
                        </a>
                        </li>
                    </ul>
                <ul class="nav-main-submenu">
                <li class="nav-main-item">
                    <a class="nav-main-link" href="{{ url('/addexpense') }}">
                        <span class="nav-main-link-name">Add expense</span>
                    </a>
                    </li>
                </ul>
            </li>
            <li>
            <a class="nav-main-link" href="{{ url('/invoices') }}">
                <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                <span class="nav-main-link-name">Invoices</span>
            </a>
            </li>
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                    <i class="nav-main-link-icon fa fa-users"></i>
                    <span class="nav-main-link-name">Instructors</span>
                </a>
                <ul class="nav-main-submenu">
                <li class="nav-main-item">
                    <a class="nav-main-link" href="{{ url('/instructors') }}">
                        <span class="nav-main-link-name">All instructors</span>
                    </a>
                    </li>
                </ul>
                <ul class="nav-main-submenu">
                <li class="nav-main-item">
                    <a class="nav-main-link" href="{{ url('/addinstructor') }}">
                        <span class="nav-main-link-name">Add instructor</span>
                    </a>
                    </li>
                </ul>
            </li>
            @endcan
            @role(['superAdmin'])
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                        <i class="nav-main-link-icon fa fa-users"></i>
                        <span class="nav-main-link-name">Administrators</span>
                    </a>
                    <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/administrators') }}">
                            <span class="nav-main-link-name">All administrators</span>
                        </a>
                        </li>
                    </ul>
                    <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ url('/addadministrator') }}">
                            <span class="nav-main-link-name">Add administrator</span>
                        </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @role(['superAdmin', 'admin'])
                <li class="nav-main-item">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-envelope"></i>
                        <span class="nav-main-link-name">Announcements</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ url('/send-announcement') }}">
                                <span class="nav-main-link-name">Make Announcement</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ url('/sms-templates') }}">
                                <span class="nav-main-link-name">Templates</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ url('#') }}">
                                <span class="nav-main-link-name">Configuration</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @role(['superAdmin'])
                <li>
                    <a class="nav-main-link" href="{{ url('/settings') }}">
                        <i class="nav-main-link-icon fa fa-cogs"></i>
                        <span class="nav-main-link-name">Settings</span>
                    </a>
                </li>
            @endcan

            @role(['instructor'])
                <li>
                    <a class="nav-main-link" href="{{ url('/scanqrcode') }}">
                        <i class="nav-main-link-icon fa fa-qrcode"></i>
                        <span class="nav-main-link-name">Scan for attendance</span>
                    </a>
                </li>
            @endcan
          </ul>
        </div>
