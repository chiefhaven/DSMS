<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['superAdmin', 'Super-Admin'];

    public function __construct()
    {
        $this->middleware('permission:view roles')->only('index');
        $this->middleware('permission:create roles')->only(['create', 'store']);
        $this->middleware('permission:edit roles')->only(['edit', 'update']);
        $this->middleware('permission:delete roles')->only('destroy');
    }

    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('roles.roles', compact('roles'));
    }

    public function create()
    {
        return view('roles.form', [
            'role' => new Role(),
            'permissionGroups' => $this->permissionGroups(),
            'selected' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('roles.index')->with('message', "Role {$role->name} created.");
    }

    public function edit(Role $role)
    {
        return view('roles.form', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('message', 'Protected roles cannot be modified.');
        }

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('roles.index')->with('message', "Role {$role->name} updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('message', 'Protected roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('message', 'Reassign the users of this role before deleting it.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('message', 'Role deleted.');
    }

    private function permissionGroups()
    {
        return Permission::orderBy('name')->pluck('name')->groupBy(
            fn ($name) => ucfirst(trim(substr($name, (int) strpos($name, ' '))))
        );
    }
}
