<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClinicalFlag extends Model
{
    protected $fillable = [
        'company_id', 'name', 'category', 'color', 'icon',
        'requires_detail', 'detail_placeholder', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'requires_detail' => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_clinical_flags', 'flag_id', 'patient_id')
                    ->withPivot('detail', 'added_by')
                    ->withTimestamps();
    }

    public function categoryColor(): string
    {
        return match ($this->category) {
            'allergy'   => '#dc2626',
            'medical'   => '#d97706',
            'lifestyle' => '#7c3aed',
            'alert'     => '#dc2626',
            default     => '#475569',
        };
    }
}
