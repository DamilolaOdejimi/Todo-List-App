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
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});

Route::group(['middleware' => ['auth:api'], 'namespace' => 'App\Http\Controllers'], function(){
    Route::get("/todo-lists", "TodoListController@list")->name('get-todo-lists');
    Route::get("/todo-list/{id}", "TodoListController@get")->name('get-todo-lists');
    Route::post("/todo-list", "TodoListController@create")->name('create-todo-list');
    Route::patch("/todo-lists", "TodoListController@update")->name('create-todo-list');
    Route::delete("/todo-lists", "TodoListController@delete")->name('delete-todo-lists');
});
