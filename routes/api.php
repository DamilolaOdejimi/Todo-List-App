<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\TodoController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login')->name('login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('audit-logs', 'getLogs')->name('get-logs');
});

Route::group(['middleware' => ['auth:api'], 'namespace' => 'App\Http\Controllers'], function(){
    Route::get("/todo-lists", "TodoListController@list")->name('get-todo-lists');
    Route::get("/todo-list/{id}", "TodoListController@get")->name('get-todo-lists');
    Route::post("/todo-list", "TodoListController@create")->name('create-todo-list');
    Route::patch("/todo-list", "TodoListController@update")->name('update-todo-list');
    Route::delete("/todo-lists", "TodoListController@delete")->name('delete-todo-lists');
    Route::delete("/todo-list/completion", "TodoListController@getCompletion")->name('get-todo-list-completion');
    Route::delete("/todo-list/link-tags", "TodoListController@linkTags")->name('link-todo-list-tags');

    Route::get("/todo-list/{listId}/tasks", "TasksController@list")->name('get-todo-list-tasks');
    Route::get("/task/{taskId}", "TasksController@get")->name('get-todo-list-task');
    Route::post("/todo-list/{listId}/task", "TasksController@create")->name('create-todo-list-task');
    Route::patch("/task/{taskId}", "TasksController@update")->name('update-todo-list-task');
    Route::delete("/todo-list/{listId}/tasks", "TasksController@delete")->name('delete-todo-list-tasks');
    Route::post("/task/{taskId}/update-status", "TasksController@updateTaskStatus")->name('delete-todo-list-tasks');

    Route::get("/task-statuses", "TasksController@getTaskStatuses")->name('get-task-statuses')->withoutMiddleware('auth:api');
    Route::get("/priority-levels", "TasksController@getPriorityLevels")->name('get-priority-levels')->withoutMiddleware('auth:api');

    Route::get("/tags", "TagsController@list")->name('get-tags');
    Route::post("/tag", "TagsController@create")->name('create-tag');
    Route::delete("/tags", "TagsController@delete")->name('delete-tags');
});
