<?php

use App\Models\Customer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('sales users can log in to the api because the role includes api access', function () {
    User::factory()->sales()->create([
        'email' => 'sales@example.com',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'sales@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'email'],
        ]);
});

test('warehouse users can not log in to the api because the role lacks api access', function () {
    User::factory()->warehouse()->create([
        'email' => 'warehouse@example.com',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'warehouse@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('protected api routes require authentication', function () {
    $this->getJson('/api/v1/customers')
        ->assertUnauthorized();
});

test('sales users can access protected api routes because the role includes api access', function () {
    $user = $this->actingAsUser('sales');

    Customer::factory()->count(3)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

test('warehouse users are forbidden from protected api routes because the role lacks api access', function () {
    $user = $this->actingAsUser('warehouse');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customers')
        ->assertForbidden();
});
