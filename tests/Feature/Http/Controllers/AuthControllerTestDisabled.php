<?php

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});



/**
 * Test login
 * Test login - 422
 * test with invalid password
 * test register - 200
 * test register - 422
 * test logout
 * test get logs api
 */
