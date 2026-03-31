<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function actingAsAdmin(array $attributes = []): User
    {
        return $this->actingAsUser('admin', $attributes + [
            'email' => 'admin@admin.com',
        ]);
    }

    protected function actingAsUser(string $role, array $attributes = []): User
    {
        $factory = User::factory();

        if (! method_exists($factory, $role)) {
            throw new \InvalidArgumentException("Unsupported test role [{$role}].");
        }

        $user = $factory->{$role}()->create($attributes);

        $this->actingAs($user);
        Sanctum::actingAs($user);

        return $user;
    }
}
