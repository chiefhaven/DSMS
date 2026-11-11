
<div class="dropdown d-inline-block">
    <button class="btn btn-primary" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
    <div class="dropdown-menu dropdown-menu-end">
        <a class="dropdown-item" href="{{ route('students.show', $student->id) }}">
            <i class="fa fa-user"></i> Profile
        </a>
        @if(auth()->user()->hasRole(['superAdmin', 'admin']))
            <a class="dropdown-item" href="{{ route('students.edit', $student->id) }}">
                <i class="fa fa-pencil"></i> Edit
            </a>
        @endif
        @if(auth()->user()->hasRole(['superAdmin']))
            <form method="POST" action="{{ route('students.destroy', $student->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item delete-confirm">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </form>
        @endif
    </div>
</div>
