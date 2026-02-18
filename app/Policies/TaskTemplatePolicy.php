<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TaskTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TaskTemplate');
    }

    public function view(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('View:TaskTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TaskTemplate');
    }

    public function update(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('Update:TaskTemplate');
    }

    public function delete(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('Delete:TaskTemplate');
    }

    public function restore(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('Restore:TaskTemplate');
    }

    public function forceDelete(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('ForceDelete:TaskTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TaskTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TaskTemplate');
    }

    public function replicate(AuthUser $authUser, TaskTemplate $taskTemplate): bool
    {
        return $authUser->can('Replicate:TaskTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TaskTemplate');
    }

}