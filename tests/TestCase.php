<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function actingAsAdmin()
    {
        $user = User::factory()->create([
            'email' => 'admin@admin.com',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }
}
