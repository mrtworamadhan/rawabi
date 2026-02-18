<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requester_id',
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'result_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'date',
    ];


    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }


    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'bg-red-100 text-red-600',
            'medium' => 'bg-orange-100 text-orange-600',
            'low' => 'bg-blue-100 text-blue-600',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'done' => 'border-green-500 text-green-600',
            'in_progress' => 'border-blue-500 text-blue-600',
            'review' => 'border-purple-500 text-purple-600',
            'pending' => 'border-orange-500 text-orange-600',
            default => 'border-gray-200 text-gray-500',
        };
    }
}