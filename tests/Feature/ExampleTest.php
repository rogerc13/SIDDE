<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Browsershot\Browsershot;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    /* public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    } */

    public function test_example(){
        
        //$html = view('acciones_formacion')->render();

        

        Browsershot::html('<h1>HELLO</h1>')
        ->save(public_path('storage/example_with_auth.png'));
    }
}
