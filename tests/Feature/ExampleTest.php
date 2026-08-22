<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_redirects_guests_to_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('home'));
    }
}
