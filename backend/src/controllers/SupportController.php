<?php

namespace App\Controllers;

use App\Models\Support;
use App\Middleware\Auth;
use App\Helpers\Response;

class SupportController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Support::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $ticket = Support::findById($id);
        if (!$ticket) Response::notFound('Support ticket not found');
        Response::success($ticket);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['subject'])) {
            Response::error('Subject is required', 400);
        }

        $ticket = Support::create($data);
        Response::success($ticket, 'Support ticket created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $ticket = Support::findById($id);
        if (!$ticket) Response::notFound('Support ticket not found');

        $data    = json_decode(file_get_contents('php://input'), true);
        $updated = Support::update($id, $data);
        Response::success($updated, 'Support ticket updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $ticket = Support::findById($id);
        if (!$ticket) Response::notFound('Support ticket not found');

        Support::delete($id);
        Response::success(null, 'Support ticket deleted');
    }
}
