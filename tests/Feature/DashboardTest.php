<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
test('page history is encrypted so the next user cannot see the previous user pages', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    expect($response->viewData('page')['encryptHistory'])->toBeTrue();
});
