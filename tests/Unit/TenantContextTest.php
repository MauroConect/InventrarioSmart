<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use RuntimeException;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_run_restores_previous_context_even_when_callback_fails(): void
    {
        $context = new TenantContext;
        $tenantA = (new Tenant)->forceFill(['id' => 10, 'name' => 'A']);
        $tenantB = (new Tenant)->forceFill(['id' => 20, 'name' => 'B']);
        $context->set($tenantA);

        try {
            $context->run($tenantB, function () use ($context): void {
                $this->assertSame(20, $context->id());
                throw new RuntimeException('fallo esperado');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('fallo esperado', $exception->getMessage());
        }

        $this->assertSame(10, $context->id());
    }

    public function test_scope_bypass_is_limited_to_callback(): void
    {
        $context = new TenantContext;

        $this->assertFalse($context->isBypassed());
        $context->runWithoutScope(function () use ($context): void {
            $this->assertTrue($context->isBypassed());
        });
        $this->assertFalse($context->isBypassed());
    }
}
