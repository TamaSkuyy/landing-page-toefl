<?php

use Inertia\Testing\AssertableInertia as Assert;

test('root renders the demo for the configured project mode', function () {
    config()->set('analytics.mode', 'ctwa');
    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->component('demo/ctwa')
        ->where('tracking.pageUrl', '/'));

    config()->set('analytics.mode', 'form');
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('demo/form')->where('paymentMode', 'internal'));
});
