<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['superAdmin'];
    private const PERMISSION_GROUP_ORDER = [
        'Student tickets',
        'Students',
        'Student payments',
        'Attendance',
        'Expenses',
        'Instructors',
        'Administrators',
        'Roles & permissions',
        'School & lessons',
        'Fleet',
        'Schedules',
        'Finance',
        'HR & Payroll',
        'Communications',
        'Settings',
        'System access',
    ];

    public function __construct()
    {
        $this->middleware('permission:view roles')->only('index');
        $this->middleware('permission:create roles')->only(['create', 'store']);
        $this->middleware('permission:edit roles')->only(['edit', 'update']);
        $this->middleware('permission:delete roles')->only('destroy');
    }

    public function index()
    {
        $roles = Role::where('guard_name', 'web')->withCount(['users', 'permissions'])->orderBy('name')->get();

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
        abort_unless($role->guard_name === 'web', 404);

        return view('roles.form', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('message', 'Protected roles cannot be modified.');
        }

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('roles.index')->with('message', "Role {$role->name} updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

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
        $groups = Permission::where('guard_name', 'web')->orderBy('name')->pluck('name')->groupBy(
            fn ($name) => $this->permissionGroup($name)
        );

        $orderedGroups = collect();
        foreach (self::PERMISSION_GROUP_ORDER as $group) {
            if ($groups->has($group)) {
                $orderedGroups->put($group, $groups->get($group));
            }
        }

        return $orderedGroups->union($groups)->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
    }

    private function permissionGroup(string $permission): string
    {
        $permission = strtolower($permission);

        return match (true) {
            str_contains($permission, 'student ticket') => 'Student tickets',
            str_contains($permission, 'student') => 'Students',
            str_contains($permission, 'attendance') => 'Attendance',
            str_contains($permission, 'expense') => 'Expenses',
            str_contains($permission, 'invoice'), str_contains($permission, 'fee payment') => 'Student payments',
            str_contains($permission, 'instructor') => 'Instructors',
            str_contains($permission, 'administrator') => 'Administrators',
            str_contains($permission, 'roles') => 'Roles & permissions',
            str_contains($permission, 'course'),
            str_contains($permission, 'lesson'),
            str_contains($permission, 'classroom'),
            str_contains($permission, 'driving license') => 'School & lessons',
            str_contains($permission, 'fleet'),
            str_contains($permission, 'vehicle location') => 'Fleet',
            str_contains($permission, 'schedule') => 'Schedules',
            str_contains($permission, 'finance') => 'Finance',
            str_contains($permission, 'payroll'),
            str_contains($permission, 'payslip'),
            str_contains($permission, 'leave'),
            str_contains($permission, 'designation'),
            str_contains($permission, 'salary grade'),
            str_contains($permission, 'hr department') => 'HR & Payroll',
            str_contains($permission, 'announcement'),
            str_contains($permission, 'notification template') => 'Communications',
            str_contains($permission, 'setting') => 'Settings',
            default => 'System access',
        };
    }
}
