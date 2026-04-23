<?php

namespace App\Controllers;

use App\Models\Task;
use App\Middleware\Auth;
use App\Helpers\Response;

class TaskController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Task::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $task = Task::findById($id);
        if (!$task) {
            Response::notFound('Task not found');
            return;
        }
        Response::success($task);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty($data['name'])) {
            Response::error('Task name is required', 400);
            return;
        }

        $task = Task::create($data);
        Response::success($task, 'Task created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $task = Task::findById($id);
        if (!$task) {
            Response::notFound('Task not found');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            Response::error('Invalid JSON payload', 400);
            return;
        }

        $updated = Task::update($id, $data);
        Response::success($updated, 'Task updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $task = Task::findById($id);
        if (!$task) {
            Response::notFound('Task not found');
            return;
        }

        Task::delete($id);
        Response::success(null, 'Task deleted');
    }
}
