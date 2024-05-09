<?php

use App\Enums\TaskStatuses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('list_id');
            $table->foreign('list_id')->references('id')->on('todo_lists')
                ->cascadeOnUpdate()->cascadeOnDelete();;
            $table->dateTime('due_date')->nullable();
            $table->string('priority_level')->nullable();
            $table->string('status')->default(TaskStatuses::NOT_STARTED);
            // $table->boolean('has_reminder')->default(false);
            // $table->string('reminder_type')->nullable();
            // $table->string('reminder_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
