<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AgentWallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgentWalletPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AgentWallet');
    }

    public function view(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('View:AgentWallet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AgentWallet');
    }

    public function update(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('Update:AgentWallet');
    }

    public function delete(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('Delete:AgentWallet');
    }

    public function restore(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('Restore:AgentWallet');
    }

    public function forceDelete(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('ForceDelete:AgentWallet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AgentWallet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AgentWallet');
    }

    public function replicate(AuthUser $authUser, AgentWallet $agentWallet): bool
    {
        return $authUser->can('Replicate:AgentWallet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AgentWallet');
    }

}