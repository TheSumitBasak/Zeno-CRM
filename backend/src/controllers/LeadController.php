<?php

namespace App\Controllers;

use App\Models\Lead;
use App\Middleware\Auth;
use App\Helpers\Response;

class LeadController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Lead::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($id);
        if (!$lead) Response::notFound('Lead not found');
        Response::success($lead);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['first_name']) || empty($data['last_name'])) {
            Response::error('First name and last name are required', 400);
        }

        $lead = Lead::create($data);
        Response::success($lead, 'Lead created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($id);
        if (!$lead) Response::notFound('Lead not found');

        $data    = json_decode(file_get_contents('php://input'), true);
        $updated = Lead::update($id, $data);
        Response::success($updated, 'Lead updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($id);
        if (!$lead) Response::notFound('Lead not found');

        Lead::delete($id);
        Response::success(null, 'Lead deleted');
    }

    public function convert(int $id): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($id);
        if (!$lead) Response::notFound('Lead not found');
        if ($lead['status'] === 'converted') Response::error('Lead already converted', 400);
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = Lead::convert($id, $data);
        Response::success($result, 'Lead converted successfully');
    }

    public function promoteSupport(int $id): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($id);
        if (!$lead) Response::notFound('Lead not found');
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = Lead::promoteSupport($id, $data);
        Response::success($result, 'Lead promoted to support ticket');
    }
}
