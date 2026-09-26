<?php

test('redirects to a role-appropriate page', function () {
    // Every visitor is assigned a demo user by SimulateUser (admin by default), so the
    // home page redirects immediately rather than rendering a welcome page. The exact
    // destination per role is covered by HomeControllerTest.
    $response = $this->get(route('home'));

    $response->assertRedirect();
});
