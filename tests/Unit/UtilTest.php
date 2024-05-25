<?php

use App\Models\TodoList;
use Illuminate\Support\Str;
use Tests\CreatesApplication;
use Tests\TestCase;

uses(TestCase::class, CreatesApplication::class);

test('test_that_audit_log_works', function ($logData) {
        $response = logAction($logData);
        $this->assertTrue($response);
})->with([
    [
        [
            'log_name' => 'Todo list created Test ' . Str::uuid(),
            'description' => 'A new Todo list created by Jane Doe',
            'resource_id' => 1,
            'resource_model' => TodoList::class,
            'user_id' => 1,
        ]
    ],
    [
        [
            'log_name' => 'Todo list created Test ' . Str::uuid(),
            'description' => 'A new Todo list created by Jim Clancy',
            'resource_id' => 1,
            'resource_model' => TodoList::class,
            'user_id' => 1,
        ]
    ]
]);
