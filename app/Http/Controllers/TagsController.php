<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Tasks;
use App\Utils\Responder;
use Illuminate\Http\Request;
use App\Interfaces\StatusCodes;
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
        $tags = Tasks::where('user_id', $this->user->id)->get();
        return Responder::send(StatusCodes::OK, $tags, 'Tasks retrieved successfully');
    }

    /**
     * Display the specified resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return Responder::send(StatusCodes::VALIDATION, $validator->errors(), 'Validation error');
        }

        $task = Tag::create([
            'label' => $request->label,
            'user_id' => $request->user_id
        ]);


        // Audit
        logAction([
            'log_name' => 'A task was created',
            'description' => 'A new Todo list task was created by ' . $this->user->first_name . " " . $this->user->last_name,
            'request_id' => $task->id,
            'request_model' => Tasks::class,
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

        $tags = Tag::whereIn('id', $request->ids)->get();
        $tagNames = $tags->pluck('name')->toArray();
        $tags->delete();

        // Audit
        logAction([
            'log_name' => 'Tags deleted',
            'description' => 'Tags ('. implode(', ', $tagNames->pluck('name')->toArray()) .') deleted by ' . $this->user->first_name . " " . $this->user->last_name,
            'request_id' => null,
            'request_model' => Tasks::class,
            'user_id' => $this->user->id,
        ]);
        return Responder::send(StatusCodes::DELETED, [], 'Tag(s) deleted successfully');
    }
}
