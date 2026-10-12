<?php

namespace App\Services\Modules;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TenantFeatureService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function catalog(): Collection
    {
        $this->ensureCatalog();

        return PlatformModule::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Flags for the tenant UI (camelCase keys for Alpine/JS).
     *
     * @return array<string, bool>
     */
    public function frontendFlags(?Tenant $tenant = null): array
    {
        return [
            'scanner' => $this->enabled('catalog.scanner', $tenant),
            'weighted' => $this->enabled('catalog.weighted', $tenant),
            'openOrders' => $this->enabled('sales.open_orders', $tenant),
            'appointments' => $this->enabled('services.appointments', $tenant),
            'credit' => $this->enabled('finance.credit', $tenant),
            'cheques' => $this->enabled('finance.cheques', $tenant),
            'afip' => $this->enabled('fiscal.afip', $tenant),
            'audit' => $this->enabled('operations.audit', $tenant),
            'support' => $this->enabled('platform.support', $tenant),
        ];
    }

    public function enabled(string $key, ?Tenant $tenant = null): bool
    {
        $tenant ??= $this->context->hasTenant() ? $this->context->tenant() : null;

        if ($tenant) {
            return TenantModule::query()
                ->where('tenant_id', $tenant->getKey())
                ->where('module_key', $key)
                ->where('enabled', true)
                ->exists();
        }

        if (! config('tenancy.legacy_mode', true)) {
            return false;
        }

        $module = collect(config('modules.catalog', []))->firstWhere('key', $key);

        return (bool) ($module['default_enabled'] ?? false);
    }

    public function sync(Tenant $tenant, array $requestedKeys, ?int $actorUserId = null): void
    {
        $this->ensureCatalog();
        $requestedKeys = array_values(array_unique($requestedKeys));
        $modules = PlatformModule::query()->whereIn('key', $requestedKeys)->get()->keyBy('key');

        if ($modules->count() !== count($requestedKeys)) {
            throw ValidationException::withMessages(['modules' => 'Uno o más módulos no son válidos.']);
        }

        foreach ($modules as $module) {
            if (! $module->is_ready) {
                throw ValidationException::withMessages([
                    'modules' => "El módulo {$module->name} todavía no está disponible.",
                ]);
            }

            $missing = array_diff($module->depends_on ?? [], $requestedKeys);
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'modules' => "El módulo {$module->name} requiere: ".implode(', ', $missing),
                ]);
            }
        }

        foreach (PlatformModule::query()->pluck('key') as $key) {
            TenantModule::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'module_key' => $key],
                [
                    'enabled' => in_array($key, $requestedKeys, true),
                    'enabled_at' => in_array($key, $requestedKeys, true) ? now() : null,
                    'enabled_by_user_id' => $actorUserId,
                ]
            );
        }

        $tenant->forceFill(['features' => $requestedKeys])->save();
    }

    public function enableDefaults(Tenant $tenant, ?int $actorUserId = null): void
    {
        $this->ensureCatalog();
        $keys = PlatformModule::query()->where('default_enabled', true)->where('is_ready', true)->pluck('key')->all();
        $this->sync($tenant, $keys, $actorUserId);
    }

    /**
     * @return list<string>
     */
    public function coreKeys(): array
    {
        return collect(config('modules.catalog', []))
            ->where('family', 'base')
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     family: string,
     *     family_label: string,
     *     depends_on: list<string>,
     *     is_ready: bool,
     *     locked: bool,
     *     enabled: bool
     * }>
     */
    public function tenantCatalog(Tenant $tenant): array
    {
        $this->ensureCatalog();

        $enabled = TenantModule::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('enabled', true)
            ->pluck('module_key')
            ->all();

        $families = config('modules.families', []);

        return collect(config('modules.catalog', []))
            ->map(function (array $meta) use ($enabled, $families): array {
                $family = $meta['family'] ?? 'base';
                $locked = $family === 'base';

                return [
                    'key' => $meta['key'],
                    'name' => $meta['name'],
                    'description' => $meta['description'] ?? '',
                    'family' => $family,
                    'family_label' => $families[$family] ?? $family,
                    'depends_on' => $meta['depends_on'] ?? [],
                    'is_ready' => (bool) ($meta['is_ready'] ?? false),
                    'locked' => $locked,
                    'enabled' => $locked || in_array($meta['key'], $enabled, true),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Sync from tenant settings: the base product stays on; add-ons are optional.
     *
     * @param  list<string>  $requestedKeys
     */
    public function syncFromSettings(Tenant $tenant, array $requestedKeys, ?int $actorUserId = null): void
    {
        $catalog = collect(config('modules.catalog', []))->keyBy('key');
        $optional = [];

        foreach (array_values(array_unique($requestedKeys)) as $key) {
            $meta = $catalog->get($key);
            if (! is_array($meta)) {
                throw ValidationException::withMessages(['modules' => 'Uno o más módulos no son válidos.']);
            }

            if (($meta['family'] ?? '') === 'base') {
                continue;
            }

            if (! ($meta['is_ready'] ?? false)) {
                throw ValidationException::withMessages([
                    'modules' => "El módulo {$meta['name']} todavía no está disponible.",
                ]);
            }

            $optional[] = $key;
        }

        $this->sync($tenant, array_values(array_unique([...$this->coreKeys(), ...$optional])), $actorUserId);
    }

    private function ensureCatalog(): void
    {
        foreach (config('modules.catalog', []) as $module) {
            $payload = collect($module)->except(['family'])->all();

            PlatformModule::query()->updateOrCreate(
                ['key' => $payload['key']],
                $payload + ['is_visible' => true]
            );
        }
    }
}
