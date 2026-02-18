<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik_karyawan',
        'full_name',
        'nickname',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'phone_number',
        'address_ktp',
        'address_domicile',
        'department',
        'department_id',
        'position',
        'join_date',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'npwp',
        'bpjs_ketenagakerjaan',
        'bpjs_kesehatan',
    ];

    protected $casts = [
        'join_date' => 'date',
        'date_of_birth' => 'date',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departmentRel(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function marketingReports(): HasMany
    {
        return $this->hasMany(MarketingReport::class);
    }
    
    public function getPossessiveNameAttribute()
    {
        return $this->nickname ? $this->nickname : explode(' ', $this->full_name)[0];
    }

    public function myTasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function todaysTasks()
    {
        return $this->myTasks()
            ->whereDate('created_at', today())
            ->orderBy('priority', 'desc');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'sales_id');
    }

    public function corporateLeads(): HasMany
    {
        return $this->hasMany(CorporateLead::class, 'sales_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'sales_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class);
    }

    public function salesBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'sales_id');
    }

    // public function currentTarget()
    // {
    //     return $this->hasOne(SalesTarget::class)->latestOfMany()
    //         ->whereYear('start_date', now()->year)
    //         ->whereMonth('start_date', now()->month);
    // }

    public function currentTarget()
    {
        return $this->hasOne(SalesTarget::class)->latestOfMany(); 
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }
    
    public function approvedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }
}