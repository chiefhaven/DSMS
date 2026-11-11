@extends('layouts.backend')

@section('content')
    @php($editing = $role->exists)
    <div class="bg-body-light">
        <div class="content content-full">
            <h1 class="fs-3 fw-semibold my-2 my-sm-3">{{ $editing ? 'Edit role' : 'Add role' }}</h1>
        </div>
    </div>

    <div class="content content-full">
        <form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="block block-rounded block-bordered">
                <div class="block-content block-content-full">
                    <div class="mb-4" style="max-width: 24rem;">
                        <label class="form-label" for="name">Role name</label>
                        <input type="text" id="name" name="name" maxlength="50"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="fs-5 fw-semibold mb-0">Permissions</h2>
                        <button type="button" class="btn btn-sm btn-alt-secondary rounded-pill px-3" id="toggle-all">Select all</button>
                    </div>
                    @error('permissions.*')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <div class="row">
                        @php($checked = old('permissions', $selected))
                        @foreach ($permissionGroups as $group => $names)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="fw-semibold border-bottom pb-1 mb-2">{{ $group }}</div>
                                @foreach ($names as $name)
                                    <div class="form-check">
                                        <input class="form-check-input permission-box" type="checkbox"
                                               name="permissions[]" value="{{ $name }}"
                                               id="perm-{{ \Illuminate\Support\Str::slug($name) }}"
                                               @checked(in_array($name, $checked, true))>
                                        <label class="form-check-label" for="perm-{{ \Illuminate\Support\Str::slug($name) }}">{{ $name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    <a href="{{ route('roles.index') }}" class="btn btn-alt-secondary rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('toggle-all').addEventListener('click', function () {
            const boxes = document.querySelectorAll('.permission-box');
            const allChecked = Array.from(boxes).every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
            this.textContent = allChecked ? 'Select all' : 'Clear all';
        });
    </script>
@endsection
