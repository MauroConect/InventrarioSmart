<?php

namespace App\Services\Comercio;

use App\Models\ConfiguracionComercio;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
                Storage::disk('public')->delete($config->logo_path);
                $config->logo_path = null;
            }

            if ($logo) {
                if ($config->logo_path) {
                    Storage::disk('public')->delete($config->logo_path);
                }

                $root = config('tenancy.storage_root', 'tenants')."/{$tenant->uuid}/branding";
                $config->logo_path = $logo->store($root, 'public');
            }

            $config->fill($attributes);
            $config->save();
            ConfiguracionComercio::limpiarCache($tenant->getKey());

            return $config->fresh();
        });
    }
}
