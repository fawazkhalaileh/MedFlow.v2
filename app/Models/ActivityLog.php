<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'branch_id', 'user_id', 'action',
        'model_type', 'model_id', 'description',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // --- Static helper to record a log entry ---
    public static function record(
        string $action,
        ?Model $model = null,
        string $description = '',
        array $oldValues = [],
        array $newValues = []
    ): void {
        $user = auth()->user();

        static::create([
            'company_id'  => $user?->company_id,
            'branch_id'   => $user?->primary_branch_id,
            'user_id'     => $user?->id,
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'description' => $description,
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
        ]);
    }
}
