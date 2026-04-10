<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    const TYPES = [
        'reception'      => 'Reception Note',
        'clinical'       => 'Clinical Note',
        'technician'     => 'Technician Note',
        'follow_up'      => 'Follow-Up Note',
        'internal'       => 'Internal Note',
        'alert'          => 'Alert',
        'session'        => 'Session Note',
        'treatment_plan' => 'Treatment Plan Note',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'notable_type', 'notable_id',
        'note_type', 'content', 'is_flagged', 'is_private',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_flagged'  => 'boolean',
        'is_private'  => 'boolean',
    ];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function getTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->note_type] ?? $this->note_type;
    }
}
