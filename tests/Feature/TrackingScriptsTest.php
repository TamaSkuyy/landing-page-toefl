<?php

test('no third party script is rendered when integration ids are empty', function () {
    config()->set('meta.pixel_id', null);
    config()->set('integrations.gtm_container_id', null);
    config()->set('integrations.ga4_measurement_id', null);
    config()->set('integrations.clarity_project_id', null);

    $this->withoutVite()->get('/')->assertOk()
        ->assertDontSee('connect.facebook.net', false)
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('clarity.ms', false);
});

test('gtm wins over direct ga4 and clarity receives attribution calls', function () {
    config()->set('integrations.gtm_container_id', 'GTM-TEST');
    config()->set('integrations.ga4_measurement_id', 'G-TEST');
    config()->set('integrations.clarity_project_id', 'clarity-test');

    $response = $this->withoutVite()->get('/')->assertOk();
    $response->assertSee('googletagmanager.com/gtm.js', false)
        ->assertDontSee('googletagmanager.com/gtag/js', false)
        ->assertSee("clarity('set', 'landing_source'", false)
        ->assertSee("clarity('identify'", false);
});
