<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Volt\Volt;

require_once __DIR__.'/../../Helpers.php';

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('navigation menu can be rendered', function () {
    // /dashboard redirects by role; a patient with a complete profile lands in the dashboard shell
    $user = makeUser(Role::Patient, makeClinic());
    makePatient($user);

    $this->actingAs($user);

    $this->followingRedirects()
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeVolt('layout.logout-button');
});

test('dashboard sends a patient to their own home', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertRedirect(route('patient.dashboard'));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
