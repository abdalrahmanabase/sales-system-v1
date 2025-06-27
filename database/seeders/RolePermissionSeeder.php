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

            // Company Name Management
            'view company names',
            'create company names',
            'edit company names',
            'delete company names',

            // Provider Management
            'view providers',
            'create providers',
            'edit providers',
            'delete providers',

            // Purchase Invoice Management
            'view purchase invoices',
            'create purchase invoices',
            'edit purchase invoices',
            'delete purchase invoices',

            // Provider Payment Management
            'view provider payments',
            'create provider payments',
            'edit provider payments',
            'delete provider payments',

            // Provider Sales Management
            'view provider sales',
            'create provider sales',
            'edit provider sales',
            'delete provider sales',

            // Super-admin only permissions
            'manage system settings',
            'manage backups',
            'delete roles',
            'delete permissions',
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
        $superAccountantRole = Role::firstOrCreate(['name' => 'super-accountant', 'guard_name' => 'web']);
        $branchManagerRole = Role::firstOrCreate(['name' => 'branch-manager', 'guard_name' => 'web']);
        $branchUserRole = Role::firstOrCreate(['name' => 'branch-user', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole->givePermissionTo([
            'view users', 'create users', 'edit users', 'delete users', 'assign roles',
            'view roles', 'create roles', 'edit roles',
            // 'view permissions', 'create permissions', 'edit permissions',
            'view sales', 'create sales', 'edit sales', 'delete sales', 'view sales reports',
            'view products', 'create products', 'edit products', 'delete products', 'manage inventory',
            'view clients', 'create clients', 'edit clients', 'delete clients',
            'view branches', 
            'view warehouses', 'create warehouses', 'edit warehouses', 'delete warehouses',
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            'view revenues', 'create revenues', 'edit revenues', 'delete revenues',
            'view profit reports',
            'view employees', 'create employees', 'edit employees', 'delete employees',
            'view attendance', 'manage attendance',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view company names', 'create company names', 'edit company names', 'delete company names',
            'view providers', 'create providers', 'edit providers', 'delete providers',
            'view purchase invoices', 'create purchase invoices', 'edit purchase invoices', 'delete purchase invoices',
            'view provider payments', 'create provider payments', 'edit provider payments', 'delete provider payments',
            'view provider sales', 'create provider sales', 'edit provider sales', 'delete provider sales',
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
            'view categories', 'create categories', 'edit categories',
            'view company names', 'create company names', 'edit company names',
            'view providers', 'create providers', 'edit providers',
            'view purchase invoices', 'create purchase invoices', 'edit purchase invoices',
            'view provider payments', 'create provider payments', 'edit provider payments',
            'view provider sales', 'create provider sales', 'edit provider sales', 'delete provider sales',
        ]);

        $cashierRole->givePermissionTo([
            'view sales', 'create sales', 'edit sales',
            'view products',
            'view clients', 'create clients', 'edit clients',
            'view branches',
            'view categories',
            'view company names',
            'view providers',
            'view purchase invoices',
            'view provider payments',
            'view provider sales',
        ]);

        $employeeRole->givePermissionTo([
            'view sales',
            'view products',
            'view clients',
            'view branches',
            'view categories',
            'view company names',
            'view providers',
            'view purchase invoices',
            'view provider payments',
            'view provider sales',
        ]);

        // Assign permissions to super-accountant
        $superAccountantRole->givePermissionTo([
            'view sales', 'create sales', 'edit sales', 'delete sales', 'view sales reports',
            'view products', 'create products', 'edit products', 'delete products', 'manage inventory',
            'view purchase invoices', 'create purchase invoices', 'edit purchase invoices', 'delete purchase invoices',
            'view provider payments', 'create provider payments', 'edit provider payments', 'delete provider payments',
            'view provider sales', 'create provider sales', 'edit provider sales', 'delete provider sales',
            'view profit reports',
        ]);

        // Assign permissions to branch-manager
        $branchManagerRole->givePermissionTo([
            'view sales', 'create sales', 'edit sales', 'delete sales', 'view sales reports',
            'view products', 'create products', 'edit products', 'delete products', 'manage inventory',
            'view purchase invoices', 'create purchase invoices', 'edit purchase invoices', 'delete purchase invoices',
            'view provider payments', 'create provider payments', 'edit provider payments', 'delete provider payments',
            'view provider sales', 'create provider sales', 'edit provider sales', 'delete provider sales',
            'view employees', 'create employees', 'edit employees', 'delete employees',
            'view clients', 'create clients', 'edit clients', 'delete clients',
        ]);

        // Assign permissions to branch-user
        $branchUserRole->givePermissionTo([
            'view sales', 'create sales', 'edit sales',
            'view products',
            'view purchase invoices', 'create purchase invoices',
            'view provider payments', 'create provider payments',
            'view provider sales', 'create provider sales',
            'view employees',
            'view clients',
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
