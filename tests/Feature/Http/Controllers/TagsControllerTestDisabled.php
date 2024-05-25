<?php

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

/**
 * test list api gets data
 *      make sure tags belong to user
 * test create api - 200
 *      test if tag belongs to user
 *      test with single and multiple 
 * test create api - 422 validation
 * test delete api
 * test delete api - 422
 * test deleting tags that have todo lists still attached
 */
