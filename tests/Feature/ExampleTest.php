<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     *
     * @test
     */
    public function example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
