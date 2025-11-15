<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'create-task',
            'update-task',
            'assign-task',
            'view-all-tasks',
            'update-task-status',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create manager role and assign all permissions
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo($permissions);

        // Create user role and assign limited permissions
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['update-task-status']);
    }
}
