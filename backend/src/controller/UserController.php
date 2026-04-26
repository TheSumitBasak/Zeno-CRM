<?php

namespace App\Controllers;

use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

class UserController
{
    public function index(): void
    {
        Auth::requireAdmin();
        Response::success(User::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAdmin();
        $user = User::findById($id);
        if (!$user)
            Response::notFound('User not found');
        Response::success($user);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            Response::error('Name, email, and password are required', 400);
        }

        $existing = User::findByEmail($data['email']);
        if ($existing) {
            Response::error('Email already in use', 400);
        }

        $user = User::create($data);
        Response::success($user, 'User created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        $user = User::findById($id);
        if (!$user)
            Response::notFound('User not found');

        $data = json_decode(file_get_contents('php://input'), true);
        $updated = User::update($id, $data);
        Response::success($updated, 'User updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAdmin();
        $user = User::findById($id);
        if (!$user)
            Response::notFound('User not found');

        User::delete($id);
        Response::success(null, 'User deleted');
    }
}
