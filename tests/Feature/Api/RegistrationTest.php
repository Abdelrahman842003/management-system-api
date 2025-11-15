<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
});

test('can register as manager', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'New Manager',
        'email' => 'newmanager@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'manager',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'timestamp',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                ],
                'token',
            ],
        ])
        ->assertJsonPath('message', 'User registered successfully')
        ->assertJsonPath('data.user.name', 'New Manager')
        ->assertJsonPath('data.user.email', 'newmanager@example.com')
        ->assertJsonPath('data.user.roles', ['manager']);

    $this->assertDatabaseHas('users', [
        'email' => 'newmanager@example.com',
    ]);

    $user = User::where('email', 'newmanager@example.com')->first();
    expect($user->hasRole('manager'))->toBeTrue();
});

test('can register as user', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'timestamp',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                ],
                'token',
            ],
        ])
        ->assertJsonPath('message', 'User registered successfully')
        ->assertJsonPath('data.user.name', 'New User')
        ->assertJsonPath('data.user.email', 'newuser@example.com')
        ->assertJsonPath('data.user.roles', ['user']);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user->hasRole('user'))->toBeTrue();
});

test('registration requires name', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('registration requires valid email', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration requires unique email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration requires password confirmation', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration requires matching password confirmation', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
        'role' => 'user',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration requires valid role', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'invalid-role',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

test('registration returns token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(201);

    $token = $response->json('data.token');
    expect($token)->not->toBeEmpty();

    // Test that token works
    $authResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/user');

    $authResponse->assertStatus(200)
        ->assertJsonPath('data.email', 'test@example.com');
});

test('can login after registration', function () {
    // Register
    $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ])->assertStatus(201);

    // Login
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'timestamp',
            'data' => [
                'user',
                'token',
            ],
        ]);
});
