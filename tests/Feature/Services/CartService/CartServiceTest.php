<?php

namespace Tests\Feature\Services\CartService;

use App\Models\User;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreate(): void
    {
        $this->actingAs(User::first());
        $this->withoutMiddleware();
        $response = $this->get('api/v1/dashboard/user/profile/show');
        $response->assertStatus(200);
    }
}
