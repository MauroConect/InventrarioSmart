<?php

namespace App\Services\Comercio;

use App\Models\ConfiguracionComercio;
use App\Models\Tenant;
use App\Services\PersistentUploadStore;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;

class ComercioConfigService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function update(Tenant $tenant, array $attributes, ?UploadedFile $logo = null, bool $removeLogo = false): ConfiguracionComercio
    {
        return $this->context->run($tenant, function () use ($tenant, $attributes, $logo, $removeLogo) {
            $config = ConfiguracionComercio::query()->firstOrNew();

            if ($removeLogo && $config->logo_path) {
                app(PersistentUploadStore::class)->delete($config->logo_path, 'public');
                $config->logo_path = null;
            }

            if ($logo) {
                if ($config->logo_path) {
                    app(PersistentUploadStore::class)->delete($config->logo_path, 'public');
                }

                $root = config('tenancy.storage_root', 'tenants')."/{$tenant->uuid}/branding";
                $config->logo_path = app(PersistentUploadStore::class)->store($logo, $root, 'public');
            }

            $config->fill($attributes);
            $config->save();
            ConfiguracionComercio::limpiarCache($tenant->getKey());

            return $config->fresh();
        });
    }
}
