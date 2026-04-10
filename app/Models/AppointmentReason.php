<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentReason extends Model
{
    protected $fillable = ['company_id', 'name', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'reason_id');
    }
}
