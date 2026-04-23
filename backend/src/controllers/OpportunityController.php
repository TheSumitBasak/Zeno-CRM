<?php

namespace App\Controllers;

use App\Models\Opportunity;
use App\Middleware\Auth;
use App\Helpers\Response;

class OpportunityController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Opportunity::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $opp = Opportunity::findById($id);
        if (!$opp) {
            Response::notFound('Opportunity not found');
            return;
        }
        Response::success($opp);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty($data['name'])) {
            Response::error('Opportunity name is required', 400);
            return;
        }

        $opp = Opportunity::create($data);
        Response::success($opp, 'Opportunity created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $opp = Opportunity::findById($id);
        if (!$opp) {
            Response::notFound('Opportunity not found');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            Response::error('Invalid JSON payload', 400);
            return;
        }

        $updated = Opportunity::update($id, $data);
        Response::success($updated, 'Opportunity updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $opp = Opportunity::findById($id);
        if (!$opp) {
            Response::notFound('Opportunity not found');
            return;
        }

        Opportunity::delete($id);
        Response::success(null, 'Opportunity deleted');
    }
}
