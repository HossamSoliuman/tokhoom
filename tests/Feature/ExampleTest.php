<?php

test('guests are sent to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
