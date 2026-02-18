<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\CorporateLead;
use App\Models\MarketingReport;
use Illuminate\Support\Facades\Auth;

class LeadObserver
{
    public function created($model)
    {
        $employeeId = Auth::user()->employee?->id;
        if (!$employeeId) return;

        $type = ($model instanceof CorporateLead) ? 'Corporate' : 'Personal';

        MarketingReport::create([
            'employee_id' => $employeeId,
            'date' => now(),
            'activity_type' => 'canvasing', 
            'description' => "Mendapatkan Lead {$type} Baru: {$model->name} ({$model->source})",
            'prospect_qty' => 1,
            'location_name' => $model->city ?? 'Online/Call',
        ]);
    }

    public function updated($model)
    {
        $employeeId = Auth::user()->employee?->id;
        if (!$employeeId) return;

        if ($model->isDirty('status')) {
            $oldStatus = $model->getOriginal('status');
            $newStatus = $model->status;
            
            $activity = match($newStatus) {
                'closing', 'deal' => 'closing',
                'contacted', 'presentation' => 'meeting',
                default => 'follow_up'
            };

            MarketingReport::create([
                'employee_id' => $employeeId,
                'date' => now(),
                'activity_type' => $activity,
                'description' => "Update Status {$model->name}: {$oldStatus} -> {$newStatus}",
                'prospect_qty' => 0, 
                'location_name' => 'Follow Up Update',
            ]);
        }
    }
}