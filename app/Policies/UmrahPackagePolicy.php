<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UmrahPackage;
use Illuminate\Auth\Access\HandlesAuthorization;

class UmrahPackagePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UmrahPackage');
    }

    public function view(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('View:UmrahPackage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UmrahPackage');
    }

    public function update(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('Update:UmrahPackage');
    }

    public function delete(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('Delete:UmrahPackage');
    }

    public function restore(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('Restore:UmrahPackage');
    }

    public function forceDelete(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('ForceDelete:UmrahPackage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UmrahPackage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UmrahPackage');
    }

    public function replicate(AuthUser $authUser, UmrahPackage $umrahPackage): bool
    {
        return $authUser->can('Replicate:UmrahPackage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UmrahPackage');
    }

}