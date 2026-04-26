<?php

namespace App\Controllers;

use App\Models\Meeting;
use App\Middleware\Auth;
use App\Helpers\Response;

class MeetingController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Meeting::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $meeting = Meeting::findById($id);
        if (!$meeting) {
            Response::notFound('Meeting not found');
            return;
        }
        Response::success($meeting);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty($data['name'])) {
            Response::error('Meeting name is required', 400);
            return;
        }

        $meeting = Meeting::create($data);
        Response::success($meeting, 'Meeting created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $meeting = Meeting::findById($id);
        if (!$meeting) {
            Response::notFound('Meeting not found');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            Response::error('Invalid JSON payload', 400);
            return;
        }

        $updated = Meeting::update($id, $data);
        Response::success($updated, 'Meeting updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $meeting = Meeting::findById($id);
        if (!$meeting) {
            Response::notFound('Meeting not found');
            return;
        }

        Meeting::delete($id);
        Response::success(null, 'Meeting deleted');
    }
}
