<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantOnboarding extends Model
{
    public const STEPS = ['verify_email', 'questionnaire', 'waiting', 'complete'];

    public const STATUSES = [
        'registered',
        'questionnaire_pending',
        'review_pending',
        'discovery_scheduled',
        'configuration_proposed',
        'implementation',
        'customer_validation',
        'training',
        'active',
        'paused',
        'rejected',
    ];

    protected $fillable = [
        'user_id',
        'tenant_id',
        'assigned_to_user_id',
        'step',
        'status',
        'draft',
        'submitted_at',
        'next_action',
        'next_action_at',
        'discovery_scheduled_at',
        'last_status_comment',
        'suggested_profile',
        'suggested_modules',
        'customer_message',
        'completed_at',
    ];

    protected $casts = [
        'draft' => 'array',
        'suggested_modules' => 'array',
        'submitted_at' => 'datetime',
        'next_action_at' => 'datetime',
        'discovery_scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OnboardingNote::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OnboardingStatusEvent::class);
    }

    public function answers(): array
    {
        return $this->draft['answers'] ?? [];
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isDelivered(): bool
    {
        return $this->completed_at !== null && $this->status === 'active';
    }
}
