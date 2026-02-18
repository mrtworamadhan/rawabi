<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CorporateLead;
use Illuminate\Auth\Access\HandlesAuthorization;

class CorporateLeadPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CorporateLead');
    }

    public function view(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('View:CorporateLead');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CorporateLead');
    }

    public function update(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('Update:CorporateLead');
    }

    public function delete(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('Delete:CorporateLead');
    }

    public function restore(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('Restore:CorporateLead');
    }

    public function forceDelete(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('ForceDelete:CorporateLead');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CorporateLead');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CorporateLead');
    }

    public function replicate(AuthUser $authUser, CorporateLead $corporateLead): bool
    {
        return $authUser->can('Replicate:CorporateLead');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CorporateLead');
    }

}