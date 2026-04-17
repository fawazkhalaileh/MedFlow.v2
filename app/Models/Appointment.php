<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    public const VISIT_TYPE_DOCTOR = 'doctor';
    public const VISIT_TYPE_TECHNICIAN = 'technician';

    public const STATUS_BOOKED = 'booked';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_WAITING_DOCTOR = 'waiting_doctor';
    public const STATUS_WAITING_TECHNICIAN = 'waiting_technician';
    public const STATUS_IN_DOCTOR_VISIT = 'in_doctor_visit';
    public const STATUS_IN_TECHNICIAN_VISIT = 'in_technician_visit';
    public const STATUS_COMPLETED_WAITING_CHECKOUT = 'completed_waiting_checkout';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_RESCHEDULED = 'rescheduled';

    // Legacy aliases preserved for older tests and modules.
    public const STATUS_SCHEDULED = self::STATUS_BOOKED;
    public const STATUS_CONFIRMED = self::STATUS_BOOKED;
    public const STATUS_IN_PROGRESS = self::STATUS_IN_TECHNICIAN_VISIT;
    public const STATUS_COMPLETED = self::STATUS_COMPLETED_WAITING_CHECKOUT;

    protected $fillable = [
        'company_id',
        'branch_id',
        'patient_id',
        'treatment_plan_id',
        'patient_package_id',
        'service_id',
        'chargeable_service_ids',
        'room_id',
        'reason_id',
        'assigned_staff_id',
        'booked_by',
        'appointment_type',
        'visit_type',
        'scheduled_at',
        'duration_minutes',
        'status',
        'session_number',
        'reason_notes',
        'front_desk_note',
        'chief_complaint',
        'clinical_notes',
        'assessment',
        'doctor_visit_outcome',
        'treatment_summary',
        'doctor_recommendations',
        'checkout_summary',
        'follow_up_required',
        'outcome_notes',
        'cancellation_reason',
        'rescheduled_from',
        'reminder_sent',
        'reminder_sent_at',
        'arrived_at',
        'provider_started_at',
        'completed_at',
        'checked_out_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'chargeable_service_ids' => 'array',
        'arrived_at' => 'datetime',
        'provider_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'follow_up_required' => 'boolean',
    ];

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

    public function patientPackage(): BelongsTo
    {
        return $this->belongsTo(PatientPackage::class);
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientAttachment::class);
    }

    public function workAttributions(): HasMany
    {
        return $this->hasMany(WorkAttribution::class);
    }

    public function packageUsage(): HasOne
    {
        return $this->hasOne(PackageUsage::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', array_merge(
                self::bookedStatuses(),
                [self::STATUS_ARRIVED, self::STATUS_WAITING_DOCTOR, self::STATUS_WAITING_TECHNICIAN]
            ));
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public static function frontDeskStatuses(): array
    {
        return [
            self::STATUS_BOOKED,
            self::STATUS_ARRIVED,
            self::STATUS_WAITING_DOCTOR,
            self::STATUS_WAITING_TECHNICIAN,
            self::STATUS_COMPLETED_WAITING_CHECKOUT,
            self::STATUS_CHECKED_OUT,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW,
        ];
    }

    public static function bookedStatuses(): array
    {
        return [self::STATUS_BOOKED, 'scheduled', 'confirmed'];
    }

    public static function completedStatuses(): array
    {
        return [self::STATUS_COMPLETED_WAITING_CHECKOUT, self::STATUS_CHECKED_OUT, 'completed'];
    }

    public function isDoctorVisit(): bool
    {
        return $this->visit_type === self::VISIT_TYPE_DOCTOR;
    }

    public function isTechnicianVisit(): bool
    {
        return $this->visit_type === self::VISIT_TYPE_TECHNICIAN;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED_WAITING_CHECKOUT || $this->status === self::STATUS_CHECKED_OUT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isNoShow(): bool
    {
        return $this->status === self::STATUS_NO_SHOW;
    }
}
