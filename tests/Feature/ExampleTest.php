<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_see_the_public_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('InventarioSmart')
            ->assertSee(route('register'), false)
            ->assertSee(route('login'), false)
            ->assertSee('Contanos tu comercio');
    }
}
