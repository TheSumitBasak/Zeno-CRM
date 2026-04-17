<?php

namespace App\Controllers;

use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

class AuthController
{
    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            Response::error('Email and password are required', 400);
        }

        $user = User::findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::error('Invalid email or password', 401);
        }

        if (!$user['is_active']) {
            Response::error('Account is disabled', 403);
        }

        User::updateLastLogin($user['id']);

        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name'],
        ];

        $token = Auth::generateToken($payload);

        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'team' => $user['team'],
            ],
        ], 'Login successful');
    }

    public function logout(): void
    {
        // With JWT, logout is handled client-side
        Response::success(null, 'Logged out successfully');
    }
}
