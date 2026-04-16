<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkAttribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'patient_id',
        'appointment_id',
        'treatment_session_id',
        'patient_package_id',
        'package_usage_id',
        'service_id',
        'source_type',
        'attributable_type',
        'attributable_id',
        'quantity',
        'revenue_amount',
        'occurred_at',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'revenue_amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatmentSession(): BelongsTo
    {
        return $this->belongsTo(TreatmentSession::class);
    }

    public function patientPackage(): BelongsTo
    {
        return $this->belongsTo(PatientPackage::class);
    }

    public function packageUsage(): BelongsTo
    {
        return $this->belongsTo(PackageUsage::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
