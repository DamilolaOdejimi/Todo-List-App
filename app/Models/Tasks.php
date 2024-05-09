<?php

namespace App\Models;

use App\Enums\PriorityLevelStatus;
use App\Enums\TaskStatuses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tasks extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'list_id',
        'due_date',
        'priority_level',
        'status',
	    // 'has_reminder',
	    // 'reminder_type',
	    // 'reminder_value',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'priority_level' => PriorityLevelStatus::class,
        'status' => TaskStatuses::class
    ];

    /**
     * Get the user that owns the Tasks
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }
}
