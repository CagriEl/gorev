<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use App\Support\ReportScope;
use App\Support\TaskWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ReportScope::scopedTaskQuery()->with(['department:id,name', 'assignee:id,name']);

        if ($status = TaskStatus::tryFrom((string) $request->query('status'))) {
            $query->where('status', $status);
        }

        return TaskResource::collection($query->latest('id')->paginate(25));
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load(['department:id,name', 'assignee:id,name']));
    }

    public function store(Request $request): TaskResource
    {
        $this->authorize('create', Task::class);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'priority' => ['required', 'string'],
            'status' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'solution_note' => ['nullable', 'string'],
            'close_proof_photos' => ['nullable', 'array'],
            'arrival_photos' => ['required', 'array', 'min:1'],
            'completion_photos' => ['nullable', 'array'],
            'assigned_at' => ['nullable', 'date'],
            'dispatched_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
        ]);

        $validated['task_code'] = Task::generateTaskCode();
        TaskWorkflow::assertRequiredFields($validated);
        $task = Task::query()->create($validated);

        return new TaskResource($task->load(['department:id,name', 'assignee:id,name']));
    }

    public function update(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'department_id' => ['sometimes', 'exists:departments,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'priority' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'solution_note' => ['nullable', 'string'],
            'close_proof_photos' => ['nullable', 'array'],
            'arrival_photos' => ['nullable', 'array'],
            'completion_photos' => ['nullable', 'array'],
            'assigned_at' => ['nullable', 'date'],
            'dispatched_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
        ]);

        if (isset($validated['status'])) {
            $to = TaskStatus::from($validated['status']);
            TaskWorkflow::assertTransition($task->status, $to);
        }
        TaskWorkflow::assertRequiredFields($validated, $task);
        $task->update($validated);

        return new TaskResource($task->refresh()->load(['department:id,name', 'assignee:id,name']));
    }
}
