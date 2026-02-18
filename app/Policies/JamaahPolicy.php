<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Jamaah;
use Illuminate\Auth\Access\HandlesAuthorization;

class JamaahPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Jamaah');
    }

    public function view(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('View:Jamaah');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Jamaah');
    }

    public function update(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('Update:Jamaah');
    }

    public function delete(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('Delete:Jamaah');
    }

    public function restore(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('Restore:Jamaah');
    }

    public function forceDelete(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('ForceDelete:Jamaah');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Jamaah');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Jamaah');
    }

    public function replicate(AuthUser $authUser, Jamaah $jamaah): bool
    {
        return $authUser->can('Replicate:Jamaah');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Jamaah');
    }

}