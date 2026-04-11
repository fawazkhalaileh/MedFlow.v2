<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'first_name', 'last_name',
        'phone', 'email', 'service_interest', 'source', 'status',
        'notes', 'assigned_to', 'converted_to_patient_id',
        'converted_at', 'created_by',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'converted_to_patient_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
