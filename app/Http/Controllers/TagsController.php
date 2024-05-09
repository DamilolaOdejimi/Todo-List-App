<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Task;
use App\Utils\Responder;
use Illuminate\Http\Request;
use App\Interfaces\StatusCodes;
use App\Models\TodoList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TagsController extends Controller
{
    protected $user;

    public function __construct() {
        $this->user = Auth::user();
    }

    /**
     * Display a listing of the resource.
     */
    public function list()
    {
        $tags = Tag::where('user_id', $this->user->id)->get();
        return Responder::send(StatusCodes::OK, $tags, 'Tasks retrieved successfully');
    }

    /**
     * Display the specified resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Tag::create([
            'name' => $request->name,
            'user_id' => $this->user->id
        ]);


        // Audit
        logAction([
            'log_name' => 'A task was created',
            'description' => 'A new Todo list task was created by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => $task->id,
            'resource_model' => Tag::class,
            'user_id' => $this->user->id,
        ]);

        return Responder::send(StatusCodes::CREATED, $task, 'Todo list task created successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:tags,id'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $tags = Tag::whereIn('id', $request->ids)->doesntHave(TodoList::class)->get();
        if($tags->count() < count($request->ids))
        {
            return Responder::send(StatusCodes::BAD_REQUEST, $validator->errors(), 'Some tags are still in use');
        }

        Tag::whereIn('id', $request->ids)->delete();

        // Audit
        logAction([
            'log_name' => 'Tags deleted',
            'description' => 'Tags deleted by ' . $this->user->first_name . " " . $this->user->last_name,
            'resource_id' => null,
            'resource_model' => Tag::class,
            'user_id' => $this->user->id,
        ]);
        return Responder::send(StatusCodes::DELETED, [], 'Tag(s) deleted successfully');
    }
}
