<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('warehouse users can authenticate using the login screen because the role includes app access', function () {
    User::factory()->warehouse()->create([
        'email' => 'warehouse@example.com',
    ]);

    $response = $this->post('/login', [
        'email' => 'warehouse@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    User::factory()->warehouse()->create([
        'email' => 'warehouse@example.com',
    ]);

    $this->post('/login', [
        'email' => 'warehouse@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->warehouse()->create([
        'email' => 'warehouse@example.com',
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users without company access can not authenticate using the login screen even if their role has app access', function () {
    $user = User::factory()->warehouse()->create([
        'email' => 'user@example.com',
    ]);

    $user->update(['company_id' => null]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
});
