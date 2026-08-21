<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

test('user can redirect to google oauth page', function () {
    $response = $this->get(route('auth.google'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('existing user can login via google oauth', function () {
    $user = User::factory()->create([
        'email' => 'john.doe@gmail.com',
        'google_id' => '1234567890',
    ]);

    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('1234567890');
    $abstractUser->shouldReceive('getEmail')->andReturn('john.doe@gmail.com');
    $abstractUser->shouldReceive('getName')->andReturn('John Doe');
    $abstractUser->shouldReceive('getNickname')->andReturn('John');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('new user can register via google oauth automatically', function () {
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('9876543210');
    $abstractUser->shouldReceive('getEmail')->andReturn('new.couple@gmail.com');
    $abstractUser->shouldReceive('getName')->andReturn('Aulia Putri');
    $abstractUser->shouldReceive('getNickname')->andReturn('Aulia');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/aulia.jpg');

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', [
        'email' => 'new.couple@gmail.com',
        'google_id' => '9876543210',
    ]);
});
