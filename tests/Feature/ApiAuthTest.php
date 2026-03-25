<?php

use App\Models\Customer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('allowlisted users can log in to the api and receive a token', function () {
    User::factory()->create([
        'email' => 'admin@admin.com',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@admin.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'email'],
        ]);
});

test('non allowlisted users can not log in to the api', function () {
    User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('protected api routes require authentication', function () {
    $this->getJson('/api/v1/customers')
        ->assertUnauthorized();
});

test('allowlisted sanctum users can access protected api routes', function () {
    $user = User::factory()->create([
        'email' => 'sadmin@sadmin.com',
    ]);

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

test('non allowlisted sanctum users are forbidden from protected api routes', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customers')
        ->assertForbidden();
});
