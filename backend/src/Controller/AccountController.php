<?php

namespace App\Controllers;

use App\Models\Account;
use App\Middleware\Auth;
use App\Helpers\Response;

class AccountController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Account::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $account = Account::findById($id);
        if (!$account)
            Response::notFound('Account not found');
        Response::success($account);
    }

    public function store(): void
    {
        $user = Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            Response::error('Account name is required', 400);
        }

        $data['created_by'] = $user['id'];
        $account = Account::create($data);
        Response::success($account, 'Account created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $account = Account::findById($id);
        if (!$account)
            Response::notFound('Account not found');

        $data = json_decode(file_get_contents('php://input'), true);
        $updated = Account::update($id, $data);
        Response::success($updated, 'Account updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $account = Account::findById($id);
        if (!$account)
            Response::notFound('Account not found');

        Account::delete($id);
        Response::success(null, 'Account deleted');
    }
}
