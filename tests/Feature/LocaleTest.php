<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home page defaults to persian locale and shared translations', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->where('locale', 'fa')
        ->where('translations.nav.home', 'خانه')
    );
});

test('user can switch locale to english', function () {
    $this->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('locale', 'en')
            ->where('translations.nav.home', 'Home')
        );
});
