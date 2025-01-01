
<div class="dropdown d-inline-block">
    <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
    <div class="dropdown-menu dropdown-menu-end">
        <a class="dropdown-item" href="{{ url('/viewstudent', $student->id) }}">
            <i class="fa fa-user"></i> Profile
        </a>
        @if(auth()->user()->hasRole(['superAdmin', 'admin']))
            <a class="dropdown-item" href="{{ url('/edit-student', $student->id) }}">
                <i class="fa fa-pencil"></i> Edit
            </a>
        @endif
        @if(auth()->user()->hasRole(['superAdmin']))
            <form method="POST" action="{{ url('student-delete', $student->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item delete-confirm">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </form>
        @endif
    </div>
</div>
