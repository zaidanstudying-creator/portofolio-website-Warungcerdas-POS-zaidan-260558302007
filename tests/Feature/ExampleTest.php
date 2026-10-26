<?php

test('the welcome page displays welcome', function () {
    actingAsUser();

    $response = $this->get(route('welcome'));

    $response->assertOk()->assertSee('Mulai Kasir');
});
