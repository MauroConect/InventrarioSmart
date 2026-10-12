<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PlatformAuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'event_uuid',
        'correlation_id',
        'tenant_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Los eventos de auditoría de plataforma son inmutables.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Los eventos de auditoría de plataforma no se pueden eliminar.');
        });
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new LogicException('Los eventos de auditoría de plataforma son inmutables.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LogicException('Los eventos de auditoría de plataforma no se pueden eliminar.');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
