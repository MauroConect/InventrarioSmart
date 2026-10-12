<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Support\Tenancy\TenantContext;
use Tests\MySqlTenantTestCase;

class TenantExperienceTest extends MySqlTenantTestCase
{
    public function test_titles_and_header_follow_the_selected_commerce_and_role(): void
    {
        [$a, $user] = $this->commerce('Almacen A');
        [$b] = $this->commerce('Servicios B');
        $b->users()->attach($user, ['role' => 'vendedor', 'status' => 'active']);
        $context = app(TenantContext::class);
        $context->run($a, fn () => Categoria::create(['nombre' => 'Solo A']));
        $context->run($b, fn () => Categoria::create(['nombre' => 'Solo B']));
        $this->signIn($a, $user);
        $this->get('/productos')->assertOk()->assertSee('<title>Productos — Almacen A</title>', false)->assertSee('Cambiar comercio')->assertDontSee('El Cristo');
        $this->getJson('/api/categorias')->assertOk()->assertSee('Solo A')->assertDontSee('Solo B');
        $this->post('/select-tenant', ['tenant_id' => $b->id])->assertRedirect(route('home'))->assertSessionHas('tenant_id', $b->id);
        $this->get('/productos')->assertOk()->assertSee('<title>Productos — Servicios B</title>', false)->assertSee('vendedor');
        $this->getJson('/api/categorias')->assertOk()->assertSee('Solo B')->assertDontSee('Solo A');
        $this->postJson('/api/productos', [])->assertForbidden();
        $this->post('/select-tenant', ['tenant_id' => 999999])->assertForbidden()->assertSessionHas('tenant_id', $b->id);
    }

    public function test_single_membership_enters_directly_and_has_no_switch_link(): void
    {
        [$a, $user] = $this->commerce();
        $this->actingAs($user, 'web');
        $this->get('/select-tenant')->assertRedirect(route('home'))->assertSessionHas('tenant_id', $a->id);
        $this->get('/productos')->assertOk()->assertDontSee('Cambiar comercio');
    }
}
