<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Middleware\Auth;
use App\Helpers\Response;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();

        $data = [
            'counts' => [
                'accounts'      => Account::count(),
                'contacts'      => Contact::count(),
                'leads'         => Lead::count(),
                'opportunities' => Opportunity::count(),
            ],
            'recent_leads'           => Lead::findRecent(5),
            'opportunities_by_stage' => Opportunity::countByStage(),
            'upcoming_tasks'         => Task::findUpcoming(5),
        ];

        Response::success($data);
    }
}
