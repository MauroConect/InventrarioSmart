<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingStatusEvent extends Model
{
    protected $fillable = [
        'tenant_onboarding_id',
        'actor_user_id',
        'from_status',
        'to_status',
        'comment',
        'next_action',
    ];

    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(TenantOnboarding::class, 'tenant_onboarding_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
