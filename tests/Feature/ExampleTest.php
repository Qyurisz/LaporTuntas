<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the homepage loads successfully.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutVite();
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Lapor Tuntas');
    }
}
