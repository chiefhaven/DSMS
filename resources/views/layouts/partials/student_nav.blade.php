<div class="content-side content-side-full">

          <ul class="nav-main Sidebar">
            <li>
              <a class="nav-main-link" class="fw-semibold text-white tracking-wide" href="{{ url('/') }}">
                <i class="nav-main-link-icon fa fa-location-arrow"></i>
                <span class="nav-main-link-name">Dashboard</span>
              </a>
            </li>
            <li class="nav-main-item">
                <a class="nav-main-link" class="fw-semibold text-white tracking-wide" href="{{ url('/attendances') }}">
                    <i class="nav-main-link-icon fa fa-user-clock"></i>
                    <span class="nav-main-link-name">Attendances</span>
                </a>
            </li>
            @include('layouts.partials.knowledgeNav')
        </div>