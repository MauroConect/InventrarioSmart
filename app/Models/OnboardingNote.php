<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingNote extends Model
{
    protected $fillable = [
        'tenant_onboarding_id',
        'user_id',
        'body',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(TenantOnboarding::class, 'tenant_onboarding_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
