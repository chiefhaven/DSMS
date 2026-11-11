@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Roles & permissions</h1>
                @can('create roles')
                <div class="flex-shrink-0 my-2 my-sm-0 ms-sm-3">
                    <a class="btn btn-primary rounded-pill px-4" href="{{ route('roles.create') }}">
                        <i class="fa fa-plus"></i>&nbsp; Add role
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if (Session::has('message'))
            <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 11em;">Actions</th>
                                <th>Role</th>
                                <th class="text-center">Users</th>
                                <th class="text-center">Permissions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="text-center">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                                            <div class="dropdown-menu dropdown-menu-end p-2">
                                                @if (in_array($role->name, ['superAdmin', 'Super-Admin'], true))
                                                    <span class="dropdown-item-text text-muted">Protected role</span>
                                                @else
                                                    @can('edit roles')
                                                    <a class="dropdown-item" href="{{ route('roles.edit', $role) }}">
                                                        <i class="fa fa-pencil me-2"></i> Edit
                                                    </a>
                                                    @endcan
                                                    @can('delete roles')
                                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <i class="fa fa-trash me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                    @endcan
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">{{ $role->name }}</td>
                                    <td class="text-center">{{ $role->users_count }}</td>
                                    <td class="text-center">
                                        {{ in_array($role->name, ['superAdmin', 'Super-Admin'], true) ? 'All' : $role->permissions_count }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
