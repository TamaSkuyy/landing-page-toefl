<?php

use App\Models\User;

test('guests are redirected to login when visiting the admin analytics dashboard', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('a non admin user is forbidden from the admin analytics dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('an admin user can visit the analytics dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertOk();
});
