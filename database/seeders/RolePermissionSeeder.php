<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            'manage permissions',
            
            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            
            // Permission Management
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            
            // Sales Management
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'view sales reports',

            // Category Management
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Product Management
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage inventory',
            
            // Client Management
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',
            
            // Branch Management
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',

            // Warehouse Management
            'view warehouses',
            'create warehouses',
            'edit warehouses',
            'delete warehouses',
            
            // Financial Management
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'view revenues',
            'create revenues',
            'edit revenues',
            'delete revenues',
            'view profit reports',
            
            // Employee Management
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view attendance',
            'manage attendance',
            
            // System Management
            'view system settings',
            'manage system settings',
            'view activity logs',
            'manage backups',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole->givePermissionTo([
            'view users', 'create users', 'edit users', 'delete users', 'assign roles',
            'view roles', 'create roles', 'edit roles',
            'view permissions', 'create permissions', 'edit permissions',
            'view sales', 'create sales', 'edit sales', 'delete sales', 'view sales reports',
            'view products', 'create products', 'edit products', 'delete products', 'manage inventory',
            'view clients', 'create clients', 'edit clients', 'delete clients',
            'view branches', 'create branches', 'edit branches', 'delete branches',
            'view warehouses', 'create warehouses', 'edit warehouses', 'delete warehouses',
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            'view revenues', 'create revenues', 'edit revenues', 'delete revenues',
            'view profit reports',
            'view employees', 'create employees', 'edit employees', 'delete employees',
            'view attendance', 'manage attendance',
            'view system settings', 'view activity logs',
        ]);

        $managerRole->givePermissionTo([
            'view users',
            'view sales', 'create sales', 'edit sales', 'view sales reports',
            'view products', 'create products', 'edit products', 'manage inventory',
            'view clients', 'create clients', 'edit clients',
            'view branches',
            'view expenses', 'create expenses', 'edit expenses',
            'view revenues', 'create revenues', 'edit revenues',
            'view profit reports',
            'view employees', 'create employees', 'edit employees',
            'view attendance', 'manage attendance',
        ]);

        $cashierRole->givePermissionTo([
            'view sales', 'create sales', 'edit sales',
            'view products',
            'view clients', 'create clients', 'edit clients',
            'view branches',
        ]);

        $employeeRole->givePermissionTo([
            'view sales',
            'view products',
            'view clients',
            'view branches',
        ]);

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('11111111'),
            ]
        );

        // Assign role if not already assigned
        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }
    }
}
