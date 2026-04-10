<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMedicalInfo extends Model
{
    protected $table = 'customer_medical_info';

    protected $fillable = [
        'customer_id', 'height_cm', 'weight_kg', 'skin_type', 'skin_tone',
        'medical_history', 'current_medications', 'allergies', 'contraindications',
        'is_pregnant', 'has_pacemaker', 'has_metal_implants', 'other_conditions',
        'insurance_provider', 'insurance_number', 'insurance_expiry', 'insurance_plan',
        'updated_by',
    ];

    protected $casts = [
        'insurance_expiry'  => 'date',
        'is_pregnant'       => 'boolean',
        'has_pacemaker'     => 'boolean',
        'has_metal_implants' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasContraindications(): bool
    {
        return !empty($this->contraindications)
            || $this->has_pacemaker
            || $this->has_metal_implants;
    }
}
