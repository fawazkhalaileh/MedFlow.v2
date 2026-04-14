<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'patient_id', 'service_id', 'name',
        'total_sessions', 'completed_sessions', 'start_date', 'end_date',
        'status', 'total_price', 'amount_paid', 'notes',
        'treatment_areas', 'session_settings', 'created_by',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'treatment_areas'  => 'array',
        'session_settings' => 'array',
        'total_price'      => 'decimal:2',
        'amount_paid'      => 'decimal:2',
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

    public function sessions(): HasMany
    {
        return $this->hasMany(TreatmentSession::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    // --- Computed ---

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->total_sessions - $this->completed_sessions);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_sessions === 0) {
            return 0;
        }
        return (int) round(($this->completed_sessions / $this->total_sessions) * 100);
    }

    public function getAmountRemainingAttribute(): float
    {
        return max(0, ($this->total_price ?? 0) - $this->amount_paid);
    }

    public function isComplete(): bool
    {
        return $this->completed_sessions >= $this->total_sessions;
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
