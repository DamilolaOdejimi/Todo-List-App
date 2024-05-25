<?php

use App\Enums\TaskStatuses;
use App\Interfaces\StatusCodes;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Support\Str;
// use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;

// uses(TestCase::class, RefreshDatabase::class)->in('Feature');

beforeEach(function () {
    $this->actor = User::first();
    $this->factoryList = TodoList::factory(1)->create([
        'user_id' => $this->actor->id
    ])->first();
});


test('todo-lists api retrieves data successfully', function () {
    $response = $this->actingAs($this->actor)
        ->getJson('/api/todo-lists');

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();
    expect($data)->toHaveProperties(['status', 'data', 'message']);

    // check if all keys exist and all items belong to the actor
    if(!empty($data->data)){
        expect($data->data)->each(fn($task) =>
            $task->toHaveKeys(['id', 'name', 'unique_id', 'user_id'])
                ->user_id->toEqual($this->actor->id)
        );
    }
});

test('todo-lists api retrieves paginated data successfully', function () {
    $limit = 10;
    $response = $this->actingAs($this->actor)
        ->getJson('/api/todo-lists?limit=' . $limit);

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();
    expect($data)->toHaveProperties(['status', 'data', 'meta', 'message'])
        ->meta->toHaveKey('per_page', $limit);
});


test('get single todo-list api retrieves data successfully', function () {
    $tasks = Task::factory(2)->create([
        'list_id' => $this->factoryList->id
    ]);

    $completion = ($tasks->where('status', TaskStatuses::COMPLETED)->count() / $tasks->count()) * 100;

    $response = $this->actingAs($this->actor)
        ->getJson('/api/todo-list/' . $this->factoryList->id);

    $response->assertStatus(StatusCodes::OK);
    $data = $response->getData();

    // check if all keys exist and all items belong to the actor
    // and if item has the right completion percent value
    expect($data)->toHaveProperties(['status', 'data', 'message'])
        ->data->not->toBeEmpty()
        ->toHaveKeys(['id', 'name', 'unique_id', 'user_id', 'completion'])
        ->toHaveKey('completion', "$completion%")
        ->toHaveKey('user_id', $this->actor->id);

})->skip();


test('get single todo-list api returns not found response', function () {
    $invalidListId = (TodoList::latest()->first()->id ?? 0) + 99;
    $response = $this->actingAs($this->actor)
        ->getJson('/api/todo-list/' . $invalidListId);

    $response->assertStatus(StatusCodes::NOT_FOUND);
});


test('create todo-list api creates data successfully', function (array $payload) {
    $response = $this->actingAs($this->actor)
        ->postJson('/api/todo-list/', $payload);

    $response->assertStatus(StatusCodes::CREATED);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toHaveKeys(['id', 'name', 'unique_id', 'user_id', 'tags'])
        ->tags->toBeArray();
})->with('lists');

dataset('lists', [
    [["name" => "List no " . Str::uuid(), "tags" => []]],
    [["name" => "List " . Str::uuid(), "tags" => []]]
]);

test('create todo-list api returns validation error response', function () {
    $invalidTag = (Tag::orderBy('id', 'desc')->first()->id ?? 0) + 1;
    $payload = ['name' => "List " . Str::uuid(), 'tags' => [$invalidTag]];
    $response = $this->actingAs($this->actor)
        ->postJson('/api/todo-list/', $payload);

    $response->assertStatus(StatusCodes::VALIDATION);
});


test('update todo-list api creates data successfully', function (array $payload) {
    $list = $this->actor->todoLists()->first();

    $response = $this->actingAs($this->actor)
        ->patchJson('/api/todo-list/' . $list->id, $payload);

    $response->assertStatus(StatusCodes::UPDATED);
    $data = $response->getData();
    expect($data->data)->not->toBeEmpty()->toHaveKeys(['id', 'name', 'unique_id', 'user_id', 'tags'])
        ->tags->toBeArray();
})->with('updatelists');

dataset('updatelists', [
    [["name" => "List no " . Str::uuid(), "tags" => []]],
    [["name" => "List " . Str::uuid(), "tags" => []]]
]);


test('update todo-list api returns validation error response (no name property)', function () {
    $list = $this->actor->todoLists()->first();

    $payload = ['tags' => []];
    $response = $this->actingAs($this->actor)
        ->patchJson('/api/todo-list/' . $list->id, $payload);

    $response->assertStatus(StatusCodes::VALIDATION);
});


test('delete todo-list api is successful', function () {
    $payload = ["ids" => [$this->factoryList->id]];
    $response = $this->actingAs($this->actor)
        ->deleteJson('/api/todo-lists/', $payload);

    $response->assertStatus(StatusCodes::DELETED);

    expect(TodoList::find($this->factoryList->id))->toBe(null);
});


test('delete todo-list api returns validation error', function ($payload) {
    $payload = ["ids" => $payload];
    $response = $this->actingAs($this->actor)
        ->deleteJson('/api/todo-lists/', [$payload]);

    $response->assertStatus(StatusCodes::VALIDATION);
})->with([
    "string", [[]] // tes with invalid data type or empty array
]);
