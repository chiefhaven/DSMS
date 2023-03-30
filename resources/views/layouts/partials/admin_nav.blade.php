<div class="content-side content-side-full">
          
          <ul class="nav-main Sidebar">
            <li>
              <a class="nav-main-link" class="fw-semibold text-white tracking-wide" href="{{ url('/') }}">
                <i class="nav-main-link-icon fa fa-location-arrow"></i>
                <span class="nav-main-link-name">Dashboard</span>
              </a>
            </li>
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
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/addstudent') }}">
                        <span class="nav-main-link-name">Add student</span>
                      </a>
                    </li>
                </ul>
            </li>
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                    <i class="nav-main-link-icon fa fa-user-clock"></i>
                    <span class="nav-main-link-name">Attendances</span>
                </a>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/attendances') }}">
                        <span class="nav-main-link-name">All attendances</span>
                      </a>
                    </li>
                </ul>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/addattendance') }}">
                        <span class="nav-main-link-name">Add attendance</span>
                      </a>
                    </li>
                </ul>
            </li>
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="">
                  <i class="nav-main-link-icon fa fa-bar-chart"></i>
                  <span class="nav-main-link-name">Courses</span>
                </a>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/courses') }}">
                        <span class="nav-main-link-name">All courses</span>
                      </a>
                    </li>
                </ul>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/addcourse') }}">
                        <span class="nav-main-link-name">Add course</span>
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
            <li class="nav-main-item">
                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                    <i class="nav-main-link-icon fa fa-car"></i>
                    <span class="nav-main-link-name">Fleet</span>
                </a>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      <a class="nav-main-link" href="{{ url('/fleet') }}">
                        <span class="nav-main-link-name">All fleet</span>
                      </a>
                    </li>
                </ul>
                <ul class="nav-main-submenu">
                  <li class="nav-main-item">
                      
                    </li>
                </ul>
            </li>
            <!--  <li class="nav-main-item">
                  <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                    <i class="nav-main-link-icon fa fa-bar-chart"></i>
                    <span class="nav-main-link-name">Reports</span>
                  </a>
                  <ul class="nav-main-submenu">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="be_pages_generic_blank.html">
                          <span class="nav-main-link-name">Blank</span>
                        </a>
                      </li>
                  </ul>
              </li> -->
            <li>
              <a class="nav-main-link" href="{{ url('/settings') }}">
                <i class="nav-main-link-icon fa fa-cogs"></i>
                <span class="nav-main-link-name">Settings</span>
              </a>
            </li>
          </ul>
        </div>