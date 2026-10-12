<?php

namespace App\Models\Concerns;

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
                if ($id !== null && ! $relatedModel::query()->whereKey($id)->where((new $relatedModel)->getTenantColumn(), $context->id())->exists()) {
                    throw new LogicException("La relación {$column} no pertenece al comercio activo.");
                }
            }
        });
    }
}
