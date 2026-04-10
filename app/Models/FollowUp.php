<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'customer_id', 'assigned_to',
        'type', 'due_date', 'status', 'notes', 'outcome',
        'completed_at', 'completed_by', 'created_by',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
