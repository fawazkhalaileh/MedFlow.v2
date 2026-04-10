<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TreatmentSession extends Model
{
    protected $fillable = [
        'appointment_id', 'treatment_plan_id', 'customer_id', 'branch_id',
        'service_id', 'technician_id', 'session_number', 'started_at', 'ended_at',
        'duration_minutes', 'status', 'device_used', 'laser_settings',
        'treatment_areas', 'observations_before', 'observations_after',
        'skin_reaction', 'outcome', 'next_session_notes',
        'follow_up_required', 'created_by',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'laser_settings'   => 'array',
        'treatment_areas'  => 'array',
        'follow_up_required' => 'boolean',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInMinutes($this->ended_at);
        }
        return $this->duration_minutes;
    }
}
