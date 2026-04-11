<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'import_type',
        'filename',
        'status',
        'total_rows',
        'imported',
        'skipped',
        'errors',
        'error_details',
        'column_map',
    ];

    protected $casts = [
        'error_details' => 'array',
        'column_map'    => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
