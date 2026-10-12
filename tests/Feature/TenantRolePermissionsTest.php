<?php

namespace Tests\Feature;

use App\Models\TenantModule;
use App\Models\{Caja, User};
use App\Support\Tenancy\TenantContext;
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

    public function test_tenant_admin_cannot_replace_a_shared_users_global_credentials(): void
    {
        [$a, $admin] = $this->commerce('A'); [$b, $shared] = $this->commerce('B');
        $a->users()->attach($shared, ['role' => 'vendedor', 'status' => 'active']);
        $before = $shared->password;
        $this->signIn($a, $admin);
        $this->putJson('/api/usuarios/'.$shared->id, ['name' => $shared->name, 'email' => $shared->email, 'password' => 'Changed-Fictitious-123!', 'role' => 'stock'])->assertForbidden();
        $this->assertSame($before, $shared->fresh()->password);
        $this->putJson('/api/usuarios/'.$shared->id, ['name' => $shared->name, 'email' => $shared->email, 'role' => 'stock'])->assertOk();
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $a->id, 'user_id' => $shared->id, 'role' => 'stock']);
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $b->id, 'user_id' => $shared->id, 'role' => 'admin']);
    }

    public function test_revoking_membership_preserves_history_but_removes_operational_access(): void
    {
        [$tenant, $admin] = $this->commerce();
        $staff = User::create(['name' => 'Staff ficticio', 'email' => 'staff@example.test', 'password' => 'Fictitious-123!', 'role' => 'vendedor']);
        $staff->forceFill(['email_verified_at' => now()])->save();
        $tenant->users()->attach($staff, ['role' => 'vendedor', 'status' => 'active']);
        $cash = app(TenantContext::class)->run($tenant, fn () => Caja::create(['usuario_id' => $staff->id, 'fecha_apertura' => now(), 'monto_apertura' => 0]));
        $this->signIn($tenant, $admin);
        $this->deleteJson('/api/usuarios/'.$staff->id)->assertOk();
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $tenant->id, 'user_id' => $staff->id, 'status' => 'inactive']);
        $this->postJson('/api/cajas/'.$cash->id.'/cerrar', ['monto_real' => 0])->assertOk();
        $this->signIn($tenant, $staff);
        $this->getJson('/api/cajas')->assertForbidden();
    }
}
