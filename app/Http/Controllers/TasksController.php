<?php

namespace App\Http\Controllers;

use App\Enums\PriorityLevelStatus;
use App\Models\Task;
use App\Models\TodoList;
use App\Utils\Responder;
use App\Enums\TaskStatuses;
use Illuminate\Http\Request;
use App\Interfaces\StatusCodes;
use Illuminate\Validation\Rule;
use App\Rules\ValidateUserAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Closure;

class TasksController extends Controller
{
    protected $user;

    public function __construct() {
        $this->user = Auth::user();
    }

    /**
     * Display a listing of the resource.
     */
    public function list(int $listId)
    {
        $validator = Validator::make(['list_id' => $listId], [
            'list_id' => ['required', 'integer', new ValidateUserAsset(TodoList::class, $this->user->id)],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $tasks = Task::where('list_id', $listId)->get();
        return Responder::send(StatusCodes::OK, $tasks, 'Tasks retrieved successfully');
    }

    /**
     * Get a single record of the resource.
     */
    public function get(int $taskId)
    {
        $validator = Validator::make(['task_id' => $taskId], [
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Task::find($taskId);
        if($task->todoList->user->id != $this->user->id){
            return Responder::send(StatusCodes::FORBIDDEN, [], 'Unable to find task');
        }

        return Responder::send(StatusCodes::OK, $task, 'Task retrieved successfully');
    }

    /**
     * Display the specified resource.
     */
    public function create(Request $request, int $listId)
    {
        $validator = Validator::make(
            array_merge(['list_id' => $listId], $request->all()), [
            'list_id' => ['required', 'integer', new ValidateUserAsset(TodoList::class, $this->user->id)],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date_format:"Y-m-d H:i"', 'after:' . now()],
            'priority_level' => ['nullable', Rule::in(PriorityLevelStatus::cases())],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'list_id' => $listId,
            'due_date' => $request->due_date,
            'priority_level' => $request->priority_level,
            'status' => TaskStatuses::NOT_STARTED,
        ]);

        // Audit
        logAction([
            'log_name' => 'A task was created',
            'description' => 'A new Todo list task was created by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => $task->id,
            'resource_model' => Task::class,
            'user_id' => $this->user->id,
        ]);

        return Responder::send(StatusCodes::CREATED, $task, 'Todo list task created successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $taskId)
    {
        $validator = Validator::make(
            array_merge(['task_id' => $taskId], $request->all()), [
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date_format:"Y-m-d H:i"', 'after:' . now()->toDateTimeString()],
            'priority_level' => ['nullable', Rule::in(PriorityLevelStatus::cases())],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Task::with('todoList')->find($taskId);
        if($task->todoList->user->id != $this->user->id){
            return Responder::send(StatusCodes::FORBIDDEN, [], 'Invalid task selected');
        }

        $task->update([
            'name' => $request->name,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority_level' => $request->priority_level,
        ]);

        // Audit
        logAction([
            'log_name' => 'A task was updated',
            'description' => 'A new Todo list task was updated by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => $task->id,
            'resource_model' => Task::class,
            'user_id' => $this->user->id,
        ]);

        return Responder::send(StatusCodes::UPDATED, $task, 'Todo list task updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request, int $listId)
    {
        $validator = Validator::make(
            array_merge(['list_id' => $listId], $request->all()), [
            'list_id' => ['required', 'integer', new ValidateUserAsset(TodoList::class, $this->user->id)],
            'ids.*' => ['integer'],
            'ids' => [
                'required',
                'array',
                function(string $attribute, mixed $value, Closure $fail) use ($listId){
                    $tasks = Task::whereIn('id', $value)->get();
                    if(count($value) > $tasks->count() || $tasks->where('list_id', '!=', $listId)->isNotEmpty()){
                        $fail("Invalid task(s) has/have been selected.");
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        Task::whereIn('id', $request->ids)->delete();

        // Audit
        logAction([
            'log_name' => 'Todo list tasks deleted',
            'description' => 'Todo list tasks deleted by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => null,
            'resource_model' => Task::class,
            'user_id' => $this->user->id,
        ]);
        return Responder::send(StatusCodes::DELETED, [], 'Todo list task(s) deleted successfully');
    }

    /**
     *
     */
    public function updateTaskStatus(Request $request, int $taskId)
    {
        $validator = Validator::make(
            array_merge(['task_id' => $taskId], $request->all()), [
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'status' => ['required', 'string', Rule::in(TaskStatuses::cases())]
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Task::find($taskId);
        if($task->todoList->user->id != $this->user->id){
            return Responder::send(StatusCodes::FORBIDDEN, [], 'Invalid task selected');
        }

        $task->update([
            'status' => $request->status,
        ]);

        // Audit
        logAction([
            'log_name' => 'The status of a task was updated',
            'description' => 'Task status was updated by ' . $this->user->first_name . " " . $this->user->last_name . "to {$request->status}",
            'resource_id' => $task->id,
            'resource_model' => Task::class,
            'user_id' => $this->user->id,
        ]);

        return Responder::send(StatusCodes::UPDATED, [], 'Task status updated successfully');
    }

    /**
     *
     */
    public function getTaskStatuses()
    {
        return Responder::send(StatusCodes::OK, TaskStatuses::cases(), 'success');
    }

    /**
     *
     */
    public function getPriorityLevels()
    {
        return Responder::send(StatusCodes::OK, PriorityLevelStatus::cases(), 'success');
    }
}
