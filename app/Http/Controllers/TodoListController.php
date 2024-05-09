<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatuses;
use App\Models\TodoList;
use App\Utils\Responder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Interfaces\StatusCodes;
use Illuminate\Validation\Rule;
use App\Rules\ValidateUserAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Tag;
use App\Models\Task;

class TodoListController extends Controller
{
    protected $user;

    public function __construct() {
        $this->user = Auth::user();
    }

    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        $todoLists = TodoList::where('user_id', $this->user->id);

        if(isset($request->limit)) {
            $todoLists = $todoLists->paginate($request->limit ?? null);
        } else {
            $todoLists = $todoLists->get();
        }

        return Responder::send(StatusCodes::OK, $todoLists, 'success');
    }

    /**
     * Get a single record of the resource.
     */
    public function get(int $id)
    {
        $todoList = TodoList::with('tags')->where('user_id', $this->user->id)->where('id', $id)->first();

        if(!$todoList){
            return Responder::send(StatusCodes::NOT_FOUND, [], 'Unable to find todo list');
        }

        $tasks = $todoList->tasks()->get();
        $taskCount = $tasks->count();
        if(!$taskCount){
            $todoList->completion = "0%";
        } else {
            $completion = ($tasks->where('status', TaskStatuses::COMPLETED)->count() / $taskCount) * 100;
            $todoList->completion = "$completion%";
        }

        return Responder::send(StatusCodes::OK, $todoList, 'success');
    }

    /**
     * Create a new todo list.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tags' => ['nullable', 'array', new ValidateUserAsset(Tag::class, $this->user->id)],
            'tags.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        \DB::beginTransaction();
        try {
            $todoList = TodoList::create([
                'name' => $request->name,
                'user_id' => $this->user->id,
                'unique_id' => Str::uuid(),
            ]);

            $todoList->tags()->sync($request->tags);
            \DB::commit();

        } catch (\Exception $ex) {
            \DB::rollback();
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        // Audit
        logAction([
            'log_name' => 'Todo list created',
            'description' => 'A new Todo list created by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => $todoList->id,
            'resource_model' => TodoList::class,
            'user_id' => $this->user->id,
        ]);

        return Responder::send(StatusCodes::CREATED, $todoList->load('tags'), 'Todo list created successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make(
            array_merge(['id' => $id], $request->all()), [
            'id' => ['required', 'integer', Rule::exists(TodoList::class, 'id')->where("user_id", $this->user->id)],
            'name' => 'required|string|max:255',
            'tags' => ['nullable', 'array', new ValidateUserAsset(Tag::class, $this->user->id)],
            'tags.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        \DB::beginTransaction();
        try {
            $todoList = TodoList::find($request->id);
            $todoList->update([
                'name' => $request->name,
                'user_id' => $this->user->id,
                'unique_id' => Str::uuid(),
            ]);

            $todoList->tags()->sync($request->tags);
            \DB::commit();

        } catch (\Exception $ex) {
            \DB::rollback();
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        // Audit
        logAction([
            'log_name' => 'Todo list updated',
            'description' => 'A new Todo list was updated by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => $todoList->id,
            'resource_model' => TodoList::class,
            'user_id' => $this->user->id,
        ]);
        return Responder::send(StatusCodes::UPDATED, $todoList->load('tags'), 'Todo list created successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1', new ValidateUserAsset(TodoList::class, $this->user->id)],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        Task::whereIn('list_id', $request->ids)->delete();
        TodoList::whereIn('id', $request->ids)->delete();

        // Audit
        logAction([
            'log_name' => 'Todo list deleted',
            'description' => 'Todo lists deleted by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => null,
            'resource_model' => TodoList::class,
            'user_id' => $this->user->id,
        ]);
        return Responder::send(StatusCodes::DELETED, [], 'Todo list(s) deleted successfully');
    }
}
