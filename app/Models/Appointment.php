<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'patient_id', 'treatment_plan_id',
        'service_id', 'room_id', 'reason_id', 'assigned_staff_id', 'booked_by',
        'appointment_type', 'scheduled_at', 'duration_minutes', 'status',
        'session_number', 'reason_notes', 'outcome_notes', 'cancellation_reason',
        'rescheduled_from', 'reminder_sent', 'reminder_sent_at',
        'arrived_at', 'completed_at',
    ];

    protected $casts = [
        'scheduled_at'      => 'datetime',
        'arrived_at'        => 'datetime',
        'completed_at'      => 'datetime',
        'reminder_sent_at'  => 'datetime',
        'reminder_sent'     => 'boolean',
    ];

    // Status constants
    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_CONFIRMED  = 'confirmed';
    const STATUS_ARRIVED    = 'arrived';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_NO_SHOW    = 'no_show';
    const STATUS_RESCHEDULED = 'rescheduled';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(AppointmentReason::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function session(): HasOne
    {
        return $this->hasOne(TreatmentSession::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from');
    }

    // --- Scopes ---

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
                     ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED]);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // --- Helpers ---

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
    public function isNoShow(): bool    { return $this->status === self::STATUS_NO_SHOW; }
}
