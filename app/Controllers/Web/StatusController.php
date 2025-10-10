<?php

namespace App\Controllers\Web;

use App\Core\AuthManager;
use App\Core\View;

class StatusController extends BaseController
{
    public function index()
    {
        $user = $this->user();

        // If the user is not pending, redirect to dashboard.
        if ($user['status'] !== 'pending') {
            $this->redirect('/dashboard');
        }

        return $this->render('status/index', [], 'simple');
    }
}