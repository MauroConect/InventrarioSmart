<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;
use RuntimeException;

class TenantContext
{
    private ?Tenant $tenant = null;

    private int $bypassDepth = 0;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): Tenant
    {
        if (! $this->tenant) {
            throw new RuntimeException('No hay un tenant activo en el contexto actual.');
        }

        return $this->tenant;
    }

    public function id(): int
    {
        return (int) $this->tenant()->getKey();
    }

    public function isBypassed(): bool
    {
        return $this->bypassDepth > 0;
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $previousBypassDepth = $this->bypassDepth;
        $this->tenant = $tenant;
        $this->bypassDepth = 0;

        try {
            return $callback($tenant);
        } finally {
            $this->tenant = $previous;
            $this->bypassDepth = $previousBypassDepth;
        }
    }

    /**
     * Solo para operaciones explícitas de plataforma y migraciones.
     */
    public function runWithoutScope(Closure $callback): mixed
    {
        $this->bypassDepth++;

        try {
            return $callback();
        } finally {
            $this->bypassDepth--;
        }
    }
}
