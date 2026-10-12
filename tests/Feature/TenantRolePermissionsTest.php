<?php

namespace Tests\Feature;

use App\Models\TenantModule;
use Tests\MySqlTenantTestCase;

class TenantRolePermissionsTest extends MySqlTenantTestCase
{
    public function test_stock_finance_and_auditor_permissions_are_enforced_by_endpoints(): void
    {
        foreach (['stock', 'finanzas', 'auditor'] as $role) {
            [$tenant, $user] = $this->commerce('Rol '.$role, $role);
            foreach (['finance.credit', 'finance.cheques', 'operations.audit'] as $module) {
                TenantModule::updateOrCreate(['tenant_id' => $tenant->id, 'module_key' => $module], ['enabled' => true]);
            }
            $this->signIn($tenant, $user);
            $this->getJson('/api/user')->assertOk()->assertJsonPath('permissions', config('permissions.roles.'.$role));
            $this->getJson('/api/movimientos-stock')->assertStatus($role === 'finanzas' ? 403 : 200);
            $this->postJson('/api/movimientos-stock', [])->assertStatus($role === 'stock' ? 422 : 403);
            $this->getJson('/api/cuentas-corrientes')->assertStatus($role === 'stock' ? 403 : 200);
            $this->postJson('/api/cuentas-corrientes', [])->assertStatus($role === 'finanzas' ? 422 : 403);
            $this->getJson('/api/cheques')->assertStatus($role === 'stock' ? 403 : 200);
            $this->postJson('/api/cheques', [])->assertStatus($role === 'finanzas' ? 422 : 403);
            $this->getJson('/api/auditoria/timeline')->assertStatus($role === 'auditor' ? 200 : 403);
            $this->getJson('/api/usuarios')->assertForbidden();
            $this->putJson('/api/modulos', [])->assertForbidden();
        }
    }

    public function test_same_user_uses_membership_role_and_superadmin_has_no_implicit_access(): void
    {
        [$a, $user] = $this->commerce('A', 'stock');
        [$b] = $this->commerce('B');
        $b->users()->attach($user, ['role' => 'finanzas', 'status' => 'active']);
        $this->signIn($a, $user);
        $this->getJson('/api/user')->assertJsonPath('permissions', config('permissions.roles.stock'));
        $this->post('/select-tenant', ['tenant_id' => $b->id])->assertRedirect();
        $this->getJson('/api/user')->assertJsonPath('permissions', config('permissions.roles.finanzas'));
        $user->forceFill(['is_platform_superadmin' => true])->save();
        $user->tenants()->detach();
        $this->getJson('/api/productos')->assertForbidden();
        $this->assertFalse($user->hasPermission('productos.manage'));
    }
}
