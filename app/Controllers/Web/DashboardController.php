<?php

namespace App\Controllers\Web;

use App\Core\AuthManager;
use App\Core\View;

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        $user = $this->user();

        // Render the dashboard view within the main application layout.
        echo $this->render('pages/dashboard', [
            'pageTitle' => 'Dashboard',
            'nickname' => $user['properties']['nickname'] ?? 'User'
        ], 'layouts/app');
    }
}