<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User; // Laravel 7 namespace

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage harvest',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => 'web']
        );

        // Give all permissions to superadmin
        $superAdminRole->givePermissionTo(Permission::all());

        // Create superadmin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'johncontreras@gmail.com'],
            [
                'name' => 'JM',
                'password' => bcrypt('admin'),
            ]
        );

        // Assign role
        $superAdmin->assignRole($superAdminRole);
    }
}
