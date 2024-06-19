<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends BaseController
{
    public function index()
    {
        return $this->sendResponse('Task successfully fetch', TaskResource::collection(Task::all()), [
            'option' => Task::all()
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());

        return TaskResource::make($task);
    }
    
    public function show(Task $task)
    {

       return $this->sendResponse('Task successfully fetch', new TaskResource($task), [
       ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return TaskResource::make($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
