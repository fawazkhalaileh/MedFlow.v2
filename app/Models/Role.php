<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'company_id', 'name', 'display_name', 'color',
        'description', 'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch_roles')
                    ->withPivot('branch_id', 'is_primary');
    }

    public function hasPermission(string $module, string $action): bool
    {
        return $this->permissions()
                    ->where('module', $module)
                    ->where('action', $action)
                    ->exists();
    }
}
