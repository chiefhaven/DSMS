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
            </li>
            @include('layouts.partials.knowledgeNav')

          </ul>
        </div>
