@php($hrmActive = $hrmActive ?? '')
<div class="bg-body-light">
    <div class="content content-full py-3">
        <div class="d-flex align-items-center">
            <h1 class="fs-4 fw-semibold mb-0 me-4 flex-shrink-0"><i class="fa fa-users me-2"></i>HRM</h1>
            <ul class="nav nav-pills flex-wrap align-items-center mb-0 fs-sm" aria-label="HRM sections">
                @canany(['view leaves', 'manage leave types', 'review leave requests', 'process payroll', 'edit instructors', 'manage administrator accounts'])
                <li class="nav-item"><a class="nav-link {{ $hrmActive === 'leaves' ? 'active' : '' }}" href="{{ route('leaves.index') }}">Leaves</a></li>
                @endcanany
                <li class="nav-item"><a class="nav-link text-muted" href="#" aria-disabled="true" title="Coming soon">Attendance</a></li>
                @canany(['view payroll', 'view salary grades'])
                <li class="nav-item"><a class="nav-link {{ $hrmActive === 'payroll' ? 'active' : '' }}" href="{{ route('payroll.index') }}">Payroll</a></li>
                @endcan
                <li class="nav-item"><a class="nav-link text-muted" href="#" aria-disabled="true" title="Coming soon">Holiday</a></li>
                @can('view hr departments')
                <li class="nav-item"><a class="nav-link {{ $hrmActive === 'departments' ? 'active' : '' }}" href="{{ route('hr-departments.index') }}">Departments</a></li>
                @endcan
                @can('view designations')
                <li class="nav-item"><a class="nav-link {{ $hrmActive === 'designations' ? 'active' : '' }}" href="{{ route('designations.index') }}">Designations</a></li>
                @endcan
                @canany(['view salary grades', 'process payroll'])
                <li class="nav-item"><a class="nav-link {{ $hrmActive === 'settings' ? 'active' : '' }}" href="{{ route('hrm.settings') }}">Settings</a></li>
                @endcanany
            </ul>
        </div>
    </div>
</div>
