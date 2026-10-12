<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformModule extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'name',
        'description',
        'depends_on',
        'default_enabled',
        'is_visible',
        'is_ready',
        'sort_order',
    ];

    protected $casts = [
        'depends_on' => 'array',
        'default_enabled' => 'boolean',
        'is_visible' => 'boolean',
        'is_ready' => 'boolean',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules', 'module_key', 'tenant_id')
            ->withPivot(['enabled', 'config', 'enabled_at', 'enabled_by_user_id'])
            ->withTimestamps();
    }
}
