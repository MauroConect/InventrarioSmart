<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\Tenancy\{TenantContext, TenantRuntime};
use Tests\TestCase;

class TenantRuntimeTest extends TestCase
{
    public function test_namespace_fails_without_context_and_paths_reject_traversal(): void
    {
        $runtime = app(TenantRuntime::class);
        try { $runtime->cacheKey('x'); $this->fail('No global fallback allowed'); } catch (\RuntimeException) { $this->assertTrue(true); }
        app(TenantContext::class)->set((new Tenant)->forceFill(['id' => 1, 'uuid' => 'synthetic']));
        foreach (['../other', '/absolute', 'C:\\secrets', 'file/../../other'] as $path) {
            try { $runtime->privatePath($path); $this->fail('Traversal allowed'); } catch (\InvalidArgumentException) { $this->assertTrue(true); }
        }
        app(TenantContext::class)->clear();
    }

    public function test_tenant_execution_never_inherits_a_platform_scope_bypass(): void
    {
        $context = app(TenantContext::class);
        $tenant = (new Tenant)->forceFill(['id' => 1]);
        $context->runWithoutScope(function () use ($context, $tenant) {
            $context->run($tenant, function () use ($context) { $this->assertFalse($context->isBypassed()); $this->assertSame(1, $context->id()); });
            $this->assertTrue($context->isBypassed());
        });
        $this->assertFalse($context->isBypassed());
        $this->assertFalse($context->hasTenant());
    }
}
