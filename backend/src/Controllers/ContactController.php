<?php

namespace App\Controllers;

use App\Models\Contact;
use App\Middleware\Auth;
use App\Helpers\Response;

class ContactController
{
    public function index(): void
    {
        Auth::requireAuth();
        Response::success(Contact::findAll());
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $contact = Contact::findById($id);
        if (!$contact) Response::notFound('Contact not found');
        Response::success($contact);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['first_name']) || empty($data['last_name'])) {
            Response::error('First name and last name are required', 400);
        }

        $contact = Contact::create($data);
        Response::success($contact, 'Contact created', 201);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $contact = Contact::findById($id);
        if (!$contact) Response::notFound('Contact not found');

        $data    = json_decode(file_get_contents('php://input'), true);
        $updated = Contact::update($id, $data);
        Response::success($updated, 'Contact updated');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $contact = Contact::findById($id);
        if (!$contact) Response::notFound('Contact not found');

        Contact::delete($id);
        Response::success(null, 'Contact deleted');
    }
}
