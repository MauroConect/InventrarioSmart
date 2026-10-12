<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use LogicException;

trait HasTenantRelations
{
    public static function bootHasTenantRelations(): void
    {
        static::saving(function ($model): void {
            $context = app(TenantContext::class);
            if (! $context->hasTenant() || ($model->tenant_id !== null && (int) $model->tenant_id !== $context->id())) {
                throw new LogicException('El registro requiere el contexto de su comercio.');
            }

            foreach ($model->tenantRelations() as $column => $relatedModel) {
                $id = $model->getAttribute($column);
                if ($id === null) {
                    continue;
                }
                $query = $relatedModel::query()->whereKey($id);
                if ($relatedModel === User::class) {
                    $requiresActive = ! $model->exists || $model->isDirty($column);
                    $query->whereHas('memberships', function ($q) use ($context, $requiresActive) { $q->where('tenant_id', $context->id()); if ($requiresActive) { $q->where('status', 'active'); } });
                } else {
                    $query->where((new $relatedModel)->getTenantColumn(), $context->id());
                }
                if (! $query->exists()) {
                    throw new LogicException("La relación {$column} no pertenece al comercio activo.");
                }
            }
        });
    }
}
