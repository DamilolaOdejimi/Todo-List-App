<?php

use App\Enums\PriorityLevelStatus;
use App\Enums\TaskStatuses;
use App\Interfaces\StatusCodes;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->actor = User::first();
    $this->factoryList = TodoList::factory(1)->create([
        'user_id' => $this->actor->id
    ])->first();

    $this->factoryTask = Task::factory(1)->create([
        'list_id' => $this->factoryList->id
    ])->first();
});


test('get todo list tasks api retrieves data successfully', function () {
    $response = $this->actingAs($this->actor)
        ->getJson("/api/todo-list/" . $this->factoryList->id . "/tasks");

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();
    expect($data)->toHaveProperties(['status', 'data', 'message']);

    // check if all keys exist and all items belong to the actor
    if(!empty($data->data)){
        expect($data->data)->each(function($task) {
            $task->toHaveKeys(['id', 'name', 'description', 'list_id', 'due_date', 'priority_level', 'status'])
                ->toHaveKey('list_id', $this->factoryList->id);
        });
    }
});


test('get todo list task retrieves data successfully', function () {
    $response = $this->actingAs($this->actor)
        ->getJson('/api/task/' . $this->factoryTask->id);

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();

    // check if all keys exist and all items belong to the actor
    expect($data->data)
        ->not->toBeEmpty()
        ->toHaveKeys(['id', 'name', 'description', 'list_id', 'due_date', 'priority_level', 'status', 'todo_list'])
        ->todo_list->not->toBeEmpty();

    // test actor ownership
    expect($data->data->todo_list->user)->toMatchArray([
        'id' => $this->actor->id
    ]);
});


test('get single todo-list task api returns not found response', function () {
    $invalidTaskId = (Task::latest()->first()->id ?? 0) + 99;
    $response = $this->actingAs($this->actor)
        ->getJson("/api/task/$invalidTaskId");

    $response->assertStatus(StatusCodes::VALIDATION);
});


test('create todo-list task api creates data successfully', function (array $payload) {
    $response = $this->actingAs($this->actor)
        ->postJson("/api/todo-list/{$this->factoryList->id}/task", $payload);

    $response->assertStatus(StatusCodes::CREATED);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toHaveKeys(['id', 'name', 'description', 'list_id', 'due_date', 'priority_level', 'status']);

    // Modify due date format
    $data->data->due_date = \Carbon\Carbon::parse($data->data->due_date)->format("Y-m-d H:i");

    expect($data->data)->toMatchArray($payload);
})
->with([
    [["name" => "Random Task " . Str::uuid(), "description" => 'Description',
        'due_date' => now()->addHour()->format("Y-m-d H:i")]],
    [["name" => "Random Task " . Str::uuid(), "description" => 'Description',
        'due_date' => now()->addDay()->format("Y-m-d H:i")], 'priority_level' => PriorityLevelStatus::cases()[rand(0, 2)]->value],
]);


test('create todo-list task api returns validation error response', function (array $payload) {
    $response = $this->actingAs($this->actor)
        ->postJson("/api/todo-list/{$this->factoryList->id}/task", $payload);

    $response->assertStatus(StatusCodes::VALIDATION);
})
->with([
    [["name" => "Random Task " . Str::uuid(), "description" => 'Description',
        'due_date' => now()->subHour()->format("Y-m-d H:i:s")]],
    [["name" => "Random Task " . Str::uuid(), "description" => 'Description',
        'due_date' => now()->addDay()->format("Y-m-d H:i:"), 'priority_level' => "Wrong Priority"]],
    [["description" => 'Description',
        'due_date' => now()->addDay()->format("Y-m-d H:i:"), 'priority_level' => "Wrong Priority"]],
]);



test('update todo-list task api updates data successfully', function (array $payload) {
    $response = $this->actingAs($this->actor)
        ->patchJson('/api/task/' . $this->factoryTask->id, $payload);

    $response->assertStatus(StatusCodes::UPDATED);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toHaveKeys(['id', 'name', 'description', 'list_id', 'due_date', 'priority_level', 'status']);

    expect(TodoList::find($data->data->list_id)->user->id)->toEqual($this->actor->id);
})->with([
    [["name" => "Updated Random Task", "description" => 'Description',
        'due_date' => now()->addHour()->format("Y-m-d H:i")]],
    [["name" => "Updated Random Task", "description" => 'Description',
        'due_date' => now()->addDay()->format("Y-m-d H:i")], 'priority_level' => PriorityLevelStatus::cases()[rand(0, 2)]->value],
]);


test('test that user owns task through todo list', function (array $payload) {
    $invalidId = Task::factory(1)->create([
        'list_id' => TodoList::factory(1)->create([
            'user_id' => User::factory(1)->create()->first()->id
        ])->first()->id
    ])->first()->id;

    $response = $this->actingAs($this->actor)
        ->patchJson('/api/task/' . $invalidId, $payload);

    $response->assertStatus(StatusCodes::VALIDATION);
})
->with([
    [["name" => "Random Task " . Str::uuid(), "description" => 'Description',
        'due_date' => now()->subHour()->format("Y-m-d H:i:s")]]
]);


test('delete todo-list task api is successful', function () {
    $payload = ["ids" => [$this->factoryTask->id]];
    $response = $this->actingAs($this->actor)
        ->deleteJson("/api/todo-list/{$this->factoryList->id}/tasks", $payload);

    $response->assertStatus(StatusCodes::DELETED);

    expect(TodoList::find($this->factoryTask->id))->toBe(null);
});


test('update task status api is successful', function () {
    $status = TaskStatuses::cases()[rand(0, 2)];
    $payload = ["status" => $status->value];
    $response = $this->actingAs($this->actor)
        ->postJson("/api/task/{$this->factoryTask->id}/update-status", $payload);

    $response->assertStatus(StatusCodes::UPDATED);

    expect(Task::find($this->factoryTask->id)->status)->toBe($status);
});


test('create getTaskStatuses api returns response', function () {
    $response = $this->actingAs($this->actor)
        ->getJson("/api/task-statuses");

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toMatchArray(collect(collect(TaskStatuses::cases())->pluck('value')));
});


test('create priority-levels api returns response', function () {
    $response = $this->actingAs($this->actor)
        ->getJson("/api/priority-levels");

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toMatchArray(collect(collect(PriorityLevelStatus::cases())->pluck('value')));
});


/**
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
 */
