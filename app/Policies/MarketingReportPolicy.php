<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MarketingReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketingReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MarketingReport');
    }

    public function view(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('View:MarketingReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MarketingReport');
    }

    public function update(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('Update:MarketingReport');
    }

    public function delete(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('Delete:MarketingReport');
    }

    public function restore(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('Restore:MarketingReport');
    }

    public function forceDelete(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('ForceDelete:MarketingReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MarketingReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MarketingReport');
    }

    public function replicate(AuthUser $authUser, MarketingReport $marketingReport): bool
    {
        return $authUser->can('Replicate:MarketingReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MarketingReport');
    }

}