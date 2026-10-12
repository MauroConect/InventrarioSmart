<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    protected $fillable = [
        'tenant_id',
        'module_key',
        'enabled',
        'config',
        'enabled_at',
        'enabled_by_user_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
        'enabled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(PlatformModule::class, 'module_key', 'key');
    }
}
