<?php

use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->actor = User::first();
});


test('get todo list tasks api retrieves data successfully', function () {
    $taskId = Task::first();
    $response = $this->actingAs($this->actor)
        ->getJson("/todo-list/" . $taskId . "/tasks");

    $response->assertStatus(200);
    $data = $response->getData();

});

test('get-todo-lists retrieves data successfully', function () {
});

/**
 * Test list api gets data
 * test list api gets data paginated
 * test get api gets data
 * test get api returns 403 forbidden
 * test get api returns not found for invalid data
 * test create api - 200
 * test create api - validation 422
 * test update api - 200
 *      test that user owns task through todo list
 * test that user does not own task through todo list
 * test update api - 422 validation
 * test delete api - 204
 * test delete api - 422
 *      test validation failure of deleting a task ID from a different list
 * test task status update - 200
 * test task status update - 422
 * test task status update - 403
 * test get task status
 * test get priority level status
 */
