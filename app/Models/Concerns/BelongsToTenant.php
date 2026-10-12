<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);
            $column = $model->getTenantColumn();

            if (! $context->hasTenant()) {
                throw new LogicException('No se puede crear un registro tenant-scoped sin tenant activo.');
            }

            if ($model->getAttribute($column) === null) {
                $model->setAttribute($column, $context->id());
            }

            if ((int) $model->getAttribute($column) !== $context->id()) {
                throw new LogicException('El tenant_id del registro no coincide con el contexto activo.');
            }
        });

        static::updating(function ($model): void {
            $column = $model->getTenantColumn();

            if ($model->isDirty($column)) {
                throw new LogicException('tenant_id es inmutable.');
            }
        });
    }

    public function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
