<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'owner_user_id',
        'slug',
        'name',
        'rubro_profile',
        'features',
        'status',
        'timezone',
        'activated_at',
        'suspended_at',
        'suspension_reason',
        'onboarding_completed_at',
        'repository_url',
        'core_version',
        'environment_url',
        'delivery_notes',
    ];

    protected $casts = [
        'features' => 'array',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            $tenant->uuid ??= (string) Str::uuid();
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(TenantOnboarding::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function moduleGrants(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(PlatformModule::class, 'tenant_modules', 'tenant_id', 'module_key')
            ->withPivot(['enabled', 'config', 'enabled_at', 'enabled_by_user_id'])
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TenantUser::class)
            ->withPivot(['role', 'is_default', 'status'])
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === config('tenancy.active_status', 'active');
    }
}
