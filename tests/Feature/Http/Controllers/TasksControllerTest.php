<?php

use App\Models\User;

beforeEach(function () {
    $this->actor = User::first();
});


test('todo-lists api retrieves data successfully', function () {
    $response = $this->actingAs($this->actor)
        ->getJson('/todo-lists');

    $response->assertStatus(200);
    $data = $response->getData();

});

test('get-todo-lists retrieves data successfully', function () {
});
