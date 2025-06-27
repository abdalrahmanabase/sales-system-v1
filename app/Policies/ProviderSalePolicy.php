<?php

namespace App\Policies;

use App\Models\ProviderSale;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProviderSalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view provider sales');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProviderSale $providerSale): bool
    {
        return $user->can('view provider sales');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create provider sales');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProviderSale $providerSale): bool
    {
        return $user->can('edit provider sales');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProviderSale $providerSale): bool
    {
        return $user->can('delete provider sales');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProviderSale $providerSale): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProviderSale $providerSale): bool
    {
        return false;
    }

    public function before($user, $ability)
    {
        // Always allow super-admin
        return $user->hasRole('super-admin') ? true : null;
    }
}
