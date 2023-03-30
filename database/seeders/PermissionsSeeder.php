<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'create students']);
        Permission::create(['name' => 'delete students']);
        Permission::create(['name' => 'edit students']);
        Permission::create(['name' => 'view students']);
        Permission::create(['name' => 'view student']);
        Permission::create(['name' => 'create invoices']);
        Permission::create(['name' => 'edit invoices']);
        Permission::create(['name' => 'delete invoices']);
        Permission::create(['name' => 'create attendances']);
        Permission::create(['name' => 'delete attendances']);
        Permission::create(['name' => 'edit attendances']);
        Permission::create(['name' => 'create courses']);
        Permission::create(['name' => 'edit courses']);
        Permission::create(['name' => 'delete courses']);
        Permission::create(['name' => 'create fleet']);
        Permission::create(['name' => 'delete fleet']);
        Permission::create(['name' => 'edit fleet']);
        Permission::create(['name' => 'view dashboard']);
        Permission::create(['name' => 'create reference letters']);
        Permission::create(['name' => 'create expenses']);
        Permission::create(['name' => 'edit expenses']);
        Permission::create(['name' => 'delete expenses']);
        Permission::create(['name' => 'view settings']);

        // create roles and assign existing permissions
        $role1 = Role::create(['name' => 'instructor']);
        $role1->givePermissionTo('create attendances');
        $role1->givePermissionTo('edit attendances');
        $role1->givePermissionTo('delete attendances');
        $role1->givePermissionTo('create students');
        $role1->givePermissionTo('view students');
        $role1->givePermissionTo('edit students');
        $role1->givePermissionTo('delete students');

        $role2 = Role::create(['name' => 'admin']);
        $role2->givePermissionTo('create students');
        $role2->givePermissionTo('view students');
        $role2->givePermissionTo('edit students');
        $role2->givePermissionTo('delete students');
        $role2->givePermissionTo('create expenses');
        $role2->givePermissionTo('edit expenses');
        $role2->givePermissionTo('delete expenses');        
        $role2->givePermissionTo('view dashboard');
        $role2->givePermissionTo('create reference letters');
        $role2->givePermissionTo('view settings');

        $role3 = Role::create(['name' => 'Super-Admin']);
        // gets all permissions via Gate::before rule; see AuthServiceProvider

        // create demo users
        $user = \App\Models\User::factory()->create([
            'name' => 'Example User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole($role1);

        $user = \App\Models\User::factory()->create([
            'name' => 'Example Admin User',
            'email' => 'admin@admi.com',
        ]);
        $user->assignRole($role2);

        $user = \App\Models\User::factory()->create([
            'name' => 'Example Super-Admin User',
            'email' => 'superadmin@example.com',
        ]);
        $user->assignRole($role3);
    
    }
}
