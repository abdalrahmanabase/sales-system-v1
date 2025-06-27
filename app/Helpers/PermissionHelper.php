<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * List of permissions that only super-admin can assign or see. This is the single source of truth for access control permissions.
     */
    public static function getSuperAdminOnlyPermissions(): array
    {
        return [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            
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
            'manage permissions',

            // Branch Management
            'create branches',
            'edit branches',
            'delete branches',
            
            // System Management
            'manage system settings',
            'manage backups',
        ];
    }

    /**
     * Check if permission is super-admin only
     */
    public static function isSuperAdminOnlyPermission(string $permissionName): bool
    {
        return in_array($permissionName, self::getSuperAdminOnlyPermissions());
    }

    /**
     * Get available permissions based on user role
     */
    public static function getAvailablePermissions(): array
    {
        $permissions = \Spatie\Permission\Models\Permission::query();
        
        if (auth()->user()->hasRole('super-admin')) {
            return $permissions->pluck('name', 'id')->toArray();
        }
        
        // All other users cannot see any permissions
        return [];
    }

    /**
     * Check if user can manage users/roles/permissions
     */
    public static function canManageAccessControl(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }
} 