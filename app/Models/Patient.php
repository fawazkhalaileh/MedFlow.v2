<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $table = 'patients';

    protected $fillable = [
        'company_id', 'branch_id', 'patient_code', 'first_name', 'last_name',
        'email', 'phone', 'phone_alt', 'date_of_birth', 'gender',
        'address', 'city', 'nationality', 'id_number',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'assigned_staff_id', 'source', 'referral_source', 'status',
        'registration_date', 'last_visit_at', 'next_appointment_at',
        'consent_given', 'consent_given_at', 'internal_notes',
    ];

    protected $casts = [
        'date_of_birth'       => 'date',
        'registration_date'   => 'date',
        'last_visit_at'       => 'datetime',
        'next_appointment_at' => 'datetime',
        'consent_given_at'    => 'datetime',
        'consent_given'       => 'boolean',
    ];

    // --- Boot: auto-generate patient_code ---
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Patient $patient) {
            if (empty($patient->patient_code)) {
                $last = static::where('company_id', $patient->company_id)->max('id') ?? 0;
                $patient->patient_code = 'MF-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // --- Computed ---

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    // --- Relationships ---

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function medicalInfo(): HasOne
    {
        return $this->hasOne(PatientMedicalInfo::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function patientPackages(): HasMany
    {
        return $this->hasMany(PatientPackage::class);
    }

    public function packageUsages(): HasMany
    {
        return $this->hasMany(PackageUsage::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TreatmentSession::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function clinicalFlags(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalFlag::class, 'patient_clinical_flags', 'patient_id', 'flag_id')
                    ->withPivot('detail', 'added_by')
                    ->withTimestamps();
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // --- Helpers ---

    public function activePlan(): ?TreatmentPlan
    {
        return $this->treatmentPlans()->where('status', 'active')->latest()->first();
    }

    public function totalSessionsCompleted(): int
    {
        return $this->sessions()->where('status', 'completed')->count();
    }
}
