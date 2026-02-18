<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OfficeWallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficeWalletPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OfficeWallet');
    }

    public function view(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('View:OfficeWallet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OfficeWallet');
    }

    public function update(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('Update:OfficeWallet');
    }

    public function delete(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('Delete:OfficeWallet');
    }

    public function restore(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('Restore:OfficeWallet');
    }

    public function forceDelete(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('ForceDelete:OfficeWallet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OfficeWallet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OfficeWallet');
    }

    public function replicate(AuthUser $authUser, OfficeWallet $officeWallet): bool
    {
        return $authUser->can('Replicate:OfficeWallet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OfficeWallet');
    }

}