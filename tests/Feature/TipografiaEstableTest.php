<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipografiaEstableTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_does_not_load_a_remote_body_font_that_causes_layout_shifts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('fonts.bunny.net', false)
            ->assertSee('/fonts/figtree-latin-400-normal.woff2', false)
            ->assertSee('/fonts/alex-brush-latin-400-normal.woff2', false);
    }
}
